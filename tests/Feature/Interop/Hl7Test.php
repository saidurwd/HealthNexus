<?php

namespace Tests\Feature\Interop;

use App\Interop\Hl7\Hl7Builder;
use App\Interop\Hl7\Hl7Inbound;
use App\Interop\Hl7\Hl7Listener;
use App\Interop\Hl7\Hl7Message;
use App\Interop\Hl7\Mllp;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Encounter;
use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Laboratory\LabResult;
use App\Models\Patient;

class Hl7Test extends InteropTestCase
{
    private function peer(array $settings = []): IntegrationPartner
    {
        return $this->partner(['kind' => 'hl7_peer', 'code' => 'REFLAB', 'settings' => ['sending_application' => 'REFLAB'] + $settings, 'requires_consent' => false]);
    }

    private function adt(string $event = 'A04', string $control = 'C1', string $extra = ''): string
    {
        return "MSH|^~\\&|REFLAB|CITYLAB|HN|HOSP|20260910100000||ADT^$event^ADT_A01|$control|P|2.5.1\rEVN|$event|20260910100000\rPID|1||LAB-77^^^CITYLAB^MR~1985123456789^^^BD^NI||Chowdhury^Nasrin^Begum||19850203|F|||House 9^^Dhaka||01711111111\r$extra";
    }

    public function test_a_message_is_read_field_by_field_with_escapes_undone(): void
    {
        $m = Hl7Message::parse("MSH|^~\\&|A|B|C|D|20260910||ADT^A01|X1|P|2.5.1\rPID|1||ID1^^^HN^MR~ID2^^^BD^NI||O\\F\\Brien\\T\\Sons^Pat^M||19900101|M\rOBX|1|NM|1^Hb||13.5|g/dL\rOBX|2|ST|2^Note||line\\.br\\two\r");

        $this->assertSame('ADT^A01', $m->messageType());
        $this->assertSame('X1', $m->controlId());
        $this->assertSame('A', $m->get('MSH-3'));
        $this->assertSame('O|Brien&Sons', $m->get('PID-5.1'));
        $this->assertSame('Pat', $m->get('PID-5.2'));
        $this->assertSame(2, $m->repetitions('PID', 3));
        $this->assertSame('ID2', $m->in('PID', 0, 3, 1, 1));
        $this->assertSame('BD', $m->in('PID', 0, 3, 4, 1));
        $this->assertSame(2, $m->count('OBX'));
        $this->assertSame('13.5', $m->in('OBX', 0, 5));
        $this->assertSame("line\ntwo", $m->in('OBX', 1, 5));
        $this->assertSame('', $m->get('PV1-3'), 'a missing segment reads as empty, not an error');
    }

    public function test_text_that_is_not_hl7_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Hl7Message::parse('hello world');
    }

    public function test_mllp_frames_are_cut_out_of_a_stream_even_when_they_arrive_in_pieces(): void
    {
        $a = Mllp::frame("MSH|^~\\&|A\r");
        $b = Mllp::frame("MSH|^~\\&|B\r");
        $buffer = substr($a.$b, 0, 10);
        $this->assertSame([], Mllp::extract($buffer));
        $buffer = substr($a.$b, 0);
        $frames = Mllp::extract($buffer);
        $this->assertCount(2, $frames);
        $this->assertStringContainsString('|B', $frames[1]);
        $this->assertSame('', $buffer);
    }

    public function test_an_admission_from_a_known_sender_registers_the_patient_and_the_visit_once(): void
    {
        $this->peer();
        $ack = Hl7Message::parse(app(Hl7Inbound::class)->handle($this->adt('A01', 'C1', "PV1|1|I|||||^Dr Karim||||||||||||V-100\r")));

        $this->assertSame('AA', $ack->get('MSA-1'));
        $this->assertSame('C1', $ack->get('MSA-2'));
        $patient = Patient::query()->where('national_identifier', '1985123456789')->firstOrFail();
        $this->assertSame('Nasrin', $patient->first_name);
        $this->assertSame('Chowdhury', $patient->last_name);
        $this->assertSame('F', $patient->sex);
        $this->assertSame('1985-02-03', $patient->date_of_birth->toDateString());
        $encounter = Encounter::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('IPD', $encounter->encounter_type);

        // the same visit again with a new message id changes nothing
        app(Hl7Inbound::class)->handle($this->adt('A01', 'C2', "PV1|1|I|||||^Dr Karim||||||||||||V-100\r"));
        $this->assertSame(1, Patient::query()->where('national_identifier', '1985123456789')->count());
        $this->assertSame(1, Encounter::query()->where('patient_id', $patient->id)->count());
        $this->assertSame('processed', IntegrationMessage::query()->where('message_type', 'ADT^A01')->first()->status);
    }

    public function test_the_same_message_twice_is_acknowledged_and_done_once(): void
    {
        $this->peer();
        $first = Hl7Message::parse(app(Hl7Inbound::class)->handle($this->adt('A04', 'DUP')));
        $second = Hl7Message::parse(app(Hl7Inbound::class)->handle($this->adt('A04', 'DUP')));

        $this->assertSame('AA', $first->get('MSA-1'));
        $this->assertSame('AA', $second->get('MSA-1'));
        $this->assertStringContainsString('duplicate', $second->get('MSA-3'));
        $this->assertSame(1, IntegrationMessage::query()->count());
        $this->assertSame(1, Patient::query()->count());
    }

    public function test_an_update_changes_the_same_patient_and_never_makes_a_second(): void
    {
        $this->peer();
        app(Hl7Inbound::class)->handle($this->adt('A04', 'U1'));
        $changed = str_replace('01711111111', '01722222222', $this->adt('A08', 'U2'));
        app(Hl7Inbound::class)->handle($changed);

        $this->assertSame(1, Patient::query()->count());
        $this->assertSame('01722222222', Patient::query()->first()->phone);
    }

    public function test_an_existing_patient_is_found_by_name_and_birth_date_and_not_duplicated(): void
    {
        $this->peer();
        $existing = $this->patient(['first_name' => 'Nasrin', 'last_name' => 'Chowdhury', 'date_of_birth' => '1985-02-03', 'national_identifier' => null]);
        app(Hl7Inbound::class)->handle($this->adt('A04', 'M1'));

        $this->assertSame(1, Patient::query()->where('last_name', 'Chowdhury')->count());
        $this->assertSame('1985123456789', $existing->fresh()->national_identifier, 'the national id was added to the record we already had');
    }

    public function test_the_sender_is_known_or_refused_and_unknown_types_are_refused(): void
    {
        $this->peer();
        $stranger = str_replace('REFLAB', 'EVILCORP', $this->adt());
        $this->assertSame('AR', Hl7Message::parse(app(Hl7Inbound::class)->handle($stranger))->get('MSA-1'));
        $this->assertSame(0, Patient::query()->count());

        $order = "MSH|^~\\&|REFLAB|X|HN|H|20260910||ORM^O01|O1|P|2.5.1\rPID|1||1^^^X^MR||A^B\rORC|NW\r";
        $ack = Hl7Message::parse(app(Hl7Inbound::class)->handle($order));
        $this->assertSame('AR', $ack->get('MSA-1'));
        $this->assertStringContainsString("\rERR|", $ack->toString(), 'a refusal carries an ERR segment saying why');

        $this->assertSame('AR', Hl7Message::parse(app(Hl7Inbound::class)->handle('garbage'))->get('MSA-1'));
    }

    public function test_a_paused_sender_is_refused(): void
    {
        $p = $this->peer();
        $p->update(['status' => 'paused']);
        $this->assertSame('AR', Hl7Message::parse(app(Hl7Inbound::class)->handle($this->adt()))->get('MSA-1'));
    }

    public function test_results_from_outside_wait_for_a_person_and_never_enter_the_record_by_themselves(): void
    {
        $this->peer();
        $patient = $this->patient(['first_name' => 'Nasrin', 'last_name' => 'Chowdhury', 'date_of_birth' => '1985-02-03']);
        $oru = "MSH|^~\\&|REFLAB|CITYLAB|HN|HOSP|20260910110000||ORU^R01^ORU_R01|R1|P|2.5.1\rPID|1||LAB-77^^^CITYLAB^MR||Chowdhury^Nasrin||19850203|F\rOBR|1|P1|F1|HB^Hemoglobin|||20260910090000\rOBX|1|NM|HB^Hemoglobin||9.1|g/dL|12-16|L|||F|||20260910090000\rOBX|2|NM|WBC^White cells||7.2|10*9/L|4-11|N|||F\r";

        $ack = Hl7Message::parse(app(Hl7Inbound::class)->handle($oru));
        $this->assertSame('AA', $ack->get('MSA-1'));
        $rows = Hl7InboundResult::query()->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('new', $rows[0]->status);
        $this->assertSame($patient->id, $rows[0]->patient_id);
        $this->assertSame('9.1', $rows[0]->value);
        $this->assertSame('L', $rows[0]->abnormal_flag);
        $this->assertSame(0, LabResult::query()->count(), 'nothing was written into the laboratory record');
        $this->assertSame(0, ClinicalObservation::query()->count());
    }

    public function test_a_result_for_an_unknown_patient_asks_the_sender_to_correct_and_resend(): void
    {
        $this->peer();
        $oru = "MSH|^~\\&|REFLAB|C|HN|H|20260910||ORU^R01|R2|P|2.5.1\rPID|1||NOBODY^^^X^MR||Ghost^Person||19700101|M\rOBR|1\rOBX|1|NM|HB^Hb||9|g/dL\r";
        $ack = Hl7Message::parse(app(Hl7Inbound::class)->handle($oru));

        $this->assertSame('AE', $ack->get('MSA-1'));
        $this->assertSame(0, Hl7InboundResult::query()->count());
    }

    public function test_what_we_send_is_valid_hl7_that_reads_back(): void
    {
        $patient = $this->patient(['enterprise_patient_no' => 'EPT-000001', 'national_identifier' => '1990123456789', 'first_name' => 'Rina', 'last_name' => "O'Brien&Sons", 'date_of_birth' => '1990-04-12', 'sex' => 'F']);
        $text = app(Hl7Builder::class)->adt('A04', $patient, null, 'CTRL1');
        $m = Hl7Message::parse($text);

        $this->assertSame('ADT^A04', $m->messageType());
        $this->assertSame('CTRL1', $m->controlId());
        $this->assertSame('EPT-000001', $m->in('PID', 0, 3, 1, 0));
        $this->assertSame('1990123456789', $m->in('PID', 0, 3, 1, 1));
        $this->assertSame("O'Brien&Sons", $m->get('PID-5.1'), 'special characters survive the round trip');
        $this->assertSame('19900412', $m->get('PID-7'));
        $this->assertSame('F', $m->get('PID-8'));
    }

    public function test_a_lab_report_becomes_an_oru_with_one_result_line_each(): void
    {
        $patient = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $report = $this->labReport($patient, 'Hemoglobin');
        $m = Hl7Message::parse(app(Hl7Builder::class)->oruFromLabReport($report, 'R9'));

        $this->assertSame('ORU^R01', $m->messageType());
        $this->assertSame(1, $m->count('OBX'));
        $this->assertSame('13.5', $m->in('OBX', 0, 5));
        $this->assertSame('g/dL', $m->in('OBX', 0, 6));
        $this->assertSame('12-16', $m->in('OBX', 0, 7));
        $this->assertSame('F', $m->in('OBR', 0, 25));
    }

    public function test_the_listener_answers_each_message_on_a_connection_with_its_ack(): void
    {
        $this->peer();
        [$client, $server] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($client, Mllp::frame($this->adt('A04', 'S1')).Mllp::frame($this->adt('A08', 'S2')));

        $answered = app(Hl7Listener::class)->serve($server, 2, 2);
        $reply = '';
        stream_set_blocking($client, false);
        $reply = (string) fread($client, 65536);
        $acks = Mllp::extract($reply);

        $this->assertSame(2, $answered);
        $this->assertCount(2, $acks);
        $this->assertSame('S1', Hl7Message::parse($acks[0])->get('MSA-2'));
        $this->assertSame('AA', Hl7Message::parse($acks[1])->get('MSA-1'));
        $this->assertSame(1, IntegrationAccessLog::query()->where('action', 'receive')->groupBy('patient_id')->get()->count());
    }
}
