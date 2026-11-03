<?php

namespace App\Services\Import\Definitions;

use App\Models\Patient;
use App\Services\Import\ImportContext;
use App\Services\Patients\PatientService;
use App\Services\Search\Normalizer;

class PatientImport extends BaseImport
{
    public function key(): string
    {
        return 'patients';
    }

    public function title(): string
    {
        return 'Patients';
    }

    public function permission(): string
    {
        return 'import.patients';
    }

    public function columns(): array
    {
        return [
            ['name' => 'first_name', 'required' => true, 'example' => 'Rahim'],
            ['name' => 'middle_name', 'required' => false, 'example' => ''],
            ['name' => 'last_name', 'required' => true, 'example' => 'Uddin'],
            ['name' => 'date_of_birth', 'required' => false, 'example' => '1985-03-14', 'help' => 'YYYY-MM-DD or DD/MM/YYYY'],
            ['name' => 'sex', 'required' => false, 'example' => 'M', 'help' => 'M, F or O'],
            ['name' => 'phone', 'required' => false, 'example' => '01712345678'],
            ['name' => 'email', 'required' => false, 'example' => ''],
            ['name' => 'national_identifier', 'required' => false, 'example' => ''],
            ['name' => 'blood_group', 'required' => false, 'example' => 'O+'],
            ['name' => 'address', 'required' => false, 'example' => 'House 12, Road 5, Dhanmondi'],
            ['name' => 'city', 'required' => false, 'example' => 'Dhaka'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $sex = strtoupper((string) $this->text($raw, 'sex'));
        $sex = ['MALE' => 'M', 'FEMALE' => 'F', 'OTHER' => 'O', 'পুরুষ' => 'M', 'মহিলা' => 'F'][$sex] ?? $sex;

        $phone = $this->text($raw, 'phone');
        if ($phone !== null && ! preg_match('/^1[3-9]\d{8}$/', (string) Normalizer::phone($phone))) {
            $errors[] = 'phone must be a Bangladeshi mobile number of 11 digits starting with 01. Example: 01712345678.';
        }

        $email = $this->text($raw, 'email');
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email is not a valid address. Example: name@example.com.';
        }

        $blood = $this->text($raw, 'blood_group');
        if ($blood !== null && ! in_array(strtoupper($blood), ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
            $errors[] = 'blood_group must be one of A+, A-, B+, B-, AB+, AB-, O+, O-.';
        }

        if ($sex !== '' && ! in_array($sex, ['M', 'F', 'O'], true)) {
            $errors[] = 'sex must be M, F or O.';
        }

        $data = [
            'first_name' => $this->required($raw, 'first_name', $errors, 100),
            'middle_name' => $this->text($raw, 'middle_name'),
            'last_name' => $this->required($raw, 'last_name', $errors, 100),
            'date_of_birth' => $this->date($raw, 'date_of_birth', $errors),
            'sex' => $sex ?: null,
            'phone' => $phone,
            'email' => $email,
            'national_identifier' => $this->text($raw, 'national_identifier'),
            'blood_group' => $blood ? strtoupper($blood) : null,
            'address' => $this->text($raw, 'address'),
            'city' => $this->text($raw, 'city'),
        ];

        return ['data' => $data, 'errors' => $errors];
    }

    /** Same national ID, or same name + birth date + mobile, means the same person. */
    public function fileKey(array $data): ?string
    {
        if (! empty($data['national_identifier'])) {
            return 'nid:'.Normalizer::code($data['national_identifier']);
        }

        if (! empty($data['phone']) && ! empty($data['date_of_birth'])) {
            return 'p:'.implode('|', [implode(' ', Normalizer::nameTokens($data['first_name'].' '.$data['last_name'])), $data['date_of_birth'], Normalizer::phone($data['phone'])]);
        }

        return null;
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $query = Patient::query()->where('organization_id', $context->organizationId);

        if (! empty($data['national_identifier'])) {
            $hit = (clone $query)->where('national_identifier', $data['national_identifier'])->first();
            if ($hit) {
                return "patient {$hit->enterprise_patient_no} (same national ID)";
            }
        }

        if (! empty($data['phone']) && ! empty($data['date_of_birth'])) {
            $hit = (clone $query)->where('date_of_birth', $data['date_of_birth'])->where('first_name', $data['first_name'])
                ->where('last_name', $data['last_name'])->first();
            if ($hit && Normalizer::phone($hit->phone) === Normalizer::phone($data['phone'])) {
                return "patient {$hit->enterprise_patient_no} (same name, birth date and mobile)";
            }
        }

        return null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $payload = array_filter($data, fn ($v) => $v !== null) + ['organization_id' => $context->organizationId];
        $patient = app(PatientService::class)->createPatient($payload, $context->user, $context->branch());

        return ['type' => Patient::class, 'id' => $patient->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        return $this->removeOrDeactivate(Patient::class, $id, $context->organizationId, function (Patient $patient) {
            return $patient->encounters()->exists() || $patient->appointments()->exists() || $patient->prescriptions()->exists()
                || \DB::table('billing_invoices')->where('patient_id', $patient->id)->exists();
        });
    }
}
