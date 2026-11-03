<?php

namespace App\Interop\Hl7;

use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Writes HL7 v2.5.1 messages from our records. One place knows how to escape, how to lay out PID/PV1/OBR/OBX, and how to build an ACK. */
class Hl7Builder
{
    public function __construct(private readonly ?string $app = null, private readonly ?string $facility = null) {}

    public static function escape(?string $v): string
    {
        return strtr((string) $v, ['\\' => '\\E\\', '|' => '\\F\\', '^' => '\\S\\', '&' => '\\T\\', '~' => '\\R\\', "\r" => '', "\n" => '\\.br\\']);
    }

    private static function ts(CarbonInterface|string|null $t): string
    {
        return $t === null || $t === '' ? '' : Carbon::parse($t)->utc()->format('YmdHis');
    }

    private function msh(string $type, string $controlId, string $recvApp = '', string $recvFac = ''): string
    {
        return implode('|', ['MSH', config('interop.hl7.encoding_chars'), $this->app ?? config('interop.hl7.sending_application'), $this->facility ?? config('interop.hl7.sending_facility'), $recvApp, $recvFac, self::ts(now()), '', $type, $controlId, 'P', config('interop.hl7.version')]);
    }

    private function pid(Patient $p, string $assigningAuthority = 'HN'): string
    {
        $ids = [self::escape($p->enterprise_patient_no).'^^^'.$assigningAuthority.'^MR'];
        if ($p->national_identifier) {
            $ids[] = self::escape($p->national_identifier).'^^^BD^NI';
        }
        $sex = match (strtoupper((string) $p->sex)) {
            'M' => 'M', 'F' => 'F', 'O' => 'O', default => 'U',
        };
        $name = self::escape($p->last_name).'^'.self::escape($p->first_name).'^'.self::escape($p->middle_name);

        return implode('|', ['PID', '1', '', implode('~', $ids), '', $name, '', $p->date_of_birth?->format('Ymd') ?? '', $sex, '', '', self::escape($p->address).'^^'.self::escape($p->city), '', self::escape($p->phone)]);
    }

    private function pv1(Encounter $e, string $class): string
    {
        $doctor = User::query()->whereKey($e->provider_id)->value('name');

        return implode('|', ['PV1', '1', $class, '', '', '', '', self::escape($doctor), '', '', '', '', '', '', '', '', '', '', '', self::escape($e->encounter_no)]);
    }

    /** ADT^A04 (registration), ADT^A08 (update), ADT^A01 (admit / visit start) for a patient, with a PV1 when there is a visit. */
    public function adt(string $event, Patient $patient, ?Encounter $encounter = null, ?string $controlId = null): string
    {
        $segs = [$this->msh("ADT^$event^ADT_A01", $controlId ?? (string) Str::ulid()), 'EVN|'.$event.'|'.self::ts(now()), $this->pid($patient)];
        if ($encounter) {
            $class = in_array(strtolower((string) $encounter->encounter_type), ['ipd', 'inpatient'], true) ? 'I' : (in_array(strtolower((string) $encounter->encounter_type), ['ed', 'emergency'], true) ? 'E' : 'O');
            $segs[] = $this->pv1($encounter, $class);
        }

        return implode("\r", $segs)."\r";
    }

    /** ORU^R01 for a final lab report: one OBR for the order, one OBX per current result. */
    public function oruFromLabReport(LabReport $report, ?string $controlId = null): string
    {
        $order = LabOrder::query()->findOrFail($report->lab_order_id);
        $patient = Patient::query()->findOrFail($order->patient_id);
        $segs = [$this->msh('ORU^R01^ORU_R01', $controlId ?? (string) Str::ulid()), $this->pid($patient),
            $this->obr($order, $report)];
        $n = 0;
        $items = DB::table('lab_order_items')->where('lab_order_id', $order->id)->pluck('id');
        foreach (LabResult::query()->whereIn('lab_order_item_id', $items)->where('is_current', true)->orderBy('id')->get() as $r) {
            $test = LabTest::query()->whereKey($r->test_id)->first(['code', 'name', 'unit']);
            $numeric = $r->numeric_value !== null;
            $value = $numeric ? rtrim(rtrim((string) $r->numeric_value, '0'), '.') : (string) ($r->qualitative_value ?? $r->text_value);
            $range = $r->reference_range_text ?: ($r->reference_range_low !== null ? rtrim(rtrim((string) $r->reference_range_low, '0'), '.').'-'.rtrim(rtrim((string) $r->reference_range_high, '0'), '.') : '');
            $flag = $r->critical_flag ? 'AA' : strtoupper(substr((string) $r->abnormal_flag, 0, 2));
            $segs[] = implode('|', ['OBX', (string) ++$n, $numeric ? 'NM' : 'ST', self::escape($test?->code).'^'.self::escape($test?->name), '', self::escape($value), self::escape($r->unit ?: $test?->unit), self::escape($range), $flag, '', '', 'F', '', '', self::ts($r->reported_at)]);
        }

        return implode("\r", $segs)."\r";
    }

    private function obr(LabOrder $order, LabReport $report): string
    {
        $f = array_fill(0, 26, '');
        $f[0] = 'OBR';
        $f[1] = '1';
        $f[2] = self::escape($order->order_number);
        $f[3] = self::escape($report->report_number);
        $f[4] = 'LAB^Laboratory report';
        $f[7] = self::ts($order->ordered_at);
        $f[22] = self::ts($report->generated_at);
        $f[25] = $report->status === 'amended' ? 'C' : 'F';

        return implode('|', $f);
    }

    /** ACK / NACK for a received message. $code: AA accepted, AE application error (sender may fix and resend), AR rejected. */
    public function ack(Hl7Message $received, string $code, string $text = ''): string
    {
        $trigger = $received->get('MSH-9.2');
        $segs = [implode('|', ['MSH', config('interop.hl7.encoding_chars'), $this->app ?? config('interop.hl7.sending_application'), $this->facility ?? config('interop.hl7.sending_facility'), $received->get('MSH-3'), $received->get('MSH-4'), self::ts(now()), '', 'ACK^'.$trigger.'^ACK', (string) Str::ulid(), 'P', config('interop.hl7.version')]),
            implode('|', ['MSA', $code, $received->controlId(), self::escape(mb_substr($text, 0, 80))])];
        if ($code !== 'AA') {
            $segs[] = implode('|', ['ERR', '', '', '', $code === 'AR' ? '207^Application internal error' : '103^Table value not found', 'E', '', '', self::escape(mb_substr($text, 0, 200))]);
        }

        return implode("\r", $segs)."\r";
    }

    /** An ACK for text we could not even read as HL7. */
    public function rawNack(string $text): string
    {
        return implode("\r", [implode('|', ['MSH', config('interop.hl7.encoding_chars'), config('interop.hl7.sending_application'), config('interop.hl7.sending_facility'), '', '', self::ts(now()), '', 'ACK', (string) Str::ulid(), 'P', config('interop.hl7.version')]), implode('|', ['MSA', 'AR', '', self::escape(mb_substr($text, 0, 80))])])."\r";
    }
}
