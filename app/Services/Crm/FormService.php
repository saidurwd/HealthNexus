<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmForm;
use App\Models\Crm\CrmFormSubmission;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Digital forms (pre-visit questionnaires, intake, consent-to-contact) (S11). A form is versioned: changing its questions makes version n+1, and every answer keeps a copy of the
 * questions it was answered against, so an old answer always reads correctly. Answers are health information and are stored encrypted.
 */
class FormService
{
    public const TYPES = ['text', 'textarea', 'select', 'yesno', 'number', 'date'];

    public function __construct(private readonly AuditLogger $audit) {}

    /** @param list<array{key:string,label:string,type:string,required?:bool,options?:list<string>}> $fields */
    public function define(int $organizationId, string $code, string $title, array $fields): CrmForm
    {
        $fields = $this->checkFields($fields);
        $form = CrmForm::query()->where(['organization_id' => $organizationId, 'code' => $code])->first();
        if ($form === null) {
            $form = CrmForm::create(['organization_id' => $organizationId, 'code' => $code, 'title' => $title, 'fields' => $fields, 'version' => 1, 'status' => 'active']);
        } elseif ($form->fields !== $fields || $form->title !== $title) {
            $form->update(['title' => $title, 'fields' => $fields, 'version' => $form->version + 1, 'status' => 'active']);
        }
        $this->audit->log('UPDATE', CrmForm::class, $form->id, null, ['version' => $form->version]);

        return $form;
    }

    /** @param array<string, mixed> $answers */
    public function submit(CrmForm $form, Patient $patient, ?PatientPortalAccount $account, array $answers): CrmFormSubmission
    {
        abort_unless($form->organization_id === $patient->organization_id && $form->status === 'active', 404);
        $values = [];
        $errors = [];
        foreach ($form->fields as $f) {
            $v = $answers[$f['key']] ?? null;
            $v = is_string($v) ? trim(strip_tags($v)) : $v;
            if (($f['required'] ?? false) && ($v === null || $v === '')) {
                $errors[$f['key']] = __('This question needs an answer.');

                continue;
            }
            if ($v === null || $v === '') {
                continue;
            }
            $ok = match ($f['type']) {
                'number' => is_numeric($v),
                'date' => (bool) strtotime((string) $v),
                'yesno' => in_array($v, ['yes', 'no', true, false, 1, 0], true),
                'select' => in_array($v, $f['options'] ?? [], true),
                default => is_string($v) && mb_strlen($v) <= 2000,
            };
            if (! $ok) {
                $errors[$f['key']] = __('This answer is not valid.');

                continue;
            }
            $values[$f['key']] = $v;
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        $row = CrmFormSubmission::create(['organization_id' => $form->organization_id, 'form_id' => $form->id, 'form_version' => $form->version, 'patient_id' => $patient->id, 'account_id' => $account?->id,
            'answers' => ['fields' => $form->fields, 'values' => $values]]);
        $this->audit->log('CREATE', CrmFormSubmission::class, $row->id, null, ['form' => $form->code, 'version' => $form->version]);

        return $row;
    }

    public function review(CrmFormSubmission $row, User $by): CrmFormSubmission
    {
        $row->update(['status' => 'reviewed', 'reviewed_by' => $by->id, 'reviewed_at' => now()]);

        return $row;
    }

    /** @param list<array<string, mixed>> $fields @return list<array<string, mixed>> */
    private function checkFields(array $fields): array
    {
        $keys = [];
        foreach ($fields as $f) {
            if (! preg_match('/^[a-z][a-z0-9_]{0,39}$/', (string) ($f['key'] ?? '')) || in_array($f['key'], $keys, true) || ! in_array($f['type'] ?? '', self::TYPES, true) || trim((string) ($f['label'] ?? '')) === '') {
                throw ValidationException::withMessages(['fields' => __('Each question needs a short key, a label and a known type.')]);
            }
            if ($f['type'] === 'select' && empty($f['options'])) {
                throw ValidationException::withMessages(['fields' => __('A choice question needs options.')]);
            }
            $keys[] = $f['key'];
        }

        return $fields;
    }
}
