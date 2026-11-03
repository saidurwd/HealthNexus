<?php

namespace App\Services\Portal;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\Billing\BillingInvoice;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalThread;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

/** The shapes the web pages and the mobile API both send out. Only public ids leave the server; money is always a string with two decimals. */
final class PortalPresenter
{
    public static function patient(Patient $p): array
    {
        return ['id' => $p->public_id, 'name' => trim($p->first_name.' '.$p->last_name), 'patient_no' => $p->enterprise_patient_no, 'date_of_birth' => $p->date_of_birth?->toDateString(), 'sex' => $p->sex];
    }

    public static function provider(Provider $p): array
    {
        return ['id' => Crypt::encryptString((string) $p->id), 'name' => $p->name, 'specialty' => $p->specialty_id ? Specialty::query()->whereKey($p->specialty_id)->value('name') : null];
    }

    public static function appointment(Appointment $a): array
    {
        return ['id' => $a->public_id, 'date' => $a->appointment_date->toDateString(), 'time' => $a->appointment_time->format('H:i'), 'status' => $a->status,
            'doctor' => $a->provider_id ? Provider::query()->whereKey($a->provider_id)->value('name') : null,
            'branch' => $a->branch_id ? Branch::query()->whereKey($a->branch_id)->value('name') : null,
            'reason' => $a->reason, 'token' => AppointmentToken::query()->where('appointment_id', $a->id)->value('token_number'), 'can_change' => in_array($a->status, ['scheduled', 'confirmed'], true)];
    }

    public static function invoice(BillingInvoice $i): array
    {
        return ['id' => $i->public_id, 'number' => $i->invoice_number, 'date' => $i->invoice_date ? substr((string) $i->getRawOriginal('invoice_date'), 0, 10) : null, 'status' => $i->status, 'total' => number_format((float) $i->grand_total, 2, '.', ''), 'paid' => number_format((float) $i->paid_amount, 2, '.', ''),
            'due' => $i->remainingDue(), 'currency' => $i->currency, 'payable' => in_array($i->status, ['finalized', 'partially_paid'], true) && bccomp($i->remainingDue(), '0', 2) > 0];
    }

    public static function payment(PortalPayment $p): array
    {
        return ['id' => $p->public_id, 'status' => $p->status, 'amount' => number_format((float) $p->amount, 2, '.', ''), 'method' => $p->method, 'redirect_url' => $p->status === 'pending' ? $p->redirect_url : null, 'reason' => $p->failure_reason, 'paid_at' => $p->completed_at?->toIso8601String()];
    }

    public static function result(PortalResultRelease $r): array
    {
        return ['id' => $r->public_id, 'title' => $r->title, 'kind' => $r->source_type === 'lab_report' ? 'lab' : 'radiology', 'released_at' => $r->released_at?->toIso8601String()];
    }

    public static function prescription(Prescription $p): array
    {
        return ['id' => $p->public_id, 'number' => $p->prescription_no, 'date' => $p->issued_at?->toDateString(), 'doctor' => $p->doctor_id ? User::query()->whereKey($p->doctor_id)->value('name') : null, 'advice' => $p->advice,
            'items' => PrescriptionItem::query()->where('prescription_id', $p->id)->where('is_active', true)->orderBy('id')->get()->map(fn (PrescriptionItem $i) => ['medicine' => $i->medicine_name, 'strength' => $i->strength, 'form' => $i->dosage_form, 'frequency' => $i->frequency, 'duration' => $i->duration, 'instructions' => $i->instructions])->values()->all()];
    }

    public static function thread(PortalThread $t, bool $withMessages = false): array
    {
        $row = ['id' => $t->public_id, 'subject' => $t->subject, 'category' => $t->category, 'status' => $t->status, 'urgent' => $t->urgent, 'last_message_at' => $t->last_message_at?->toIso8601String()];
        if ($withMessages) {
            $row['messages'] = $t->messages()->orderBy('id')->get()->map(fn ($m) => ['from' => $m->sender, 'text' => $m->body, 'at' => $m->created_at?->toIso8601String()])->all();
        }

        return $row;
    }

    public static function notification(PortalNotification $n): array
    {
        return ['id' => $n->public_id, 'kind' => $n->kind, 'title' => $n->title, 'body' => $n->body, 'link' => $n->link, 'read' => $n->read_at !== null, 'at' => $n->created_at?->toIso8601String()];
    }
}
