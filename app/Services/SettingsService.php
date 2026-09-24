<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    // v2: the cache stores plain attribute arrays. Laravel's cache.serializable_classes=false turns cached
    // Eloquent objects into __PHP_Incomplete_Class on read, and the old v1 key may still hold such an entry.
    public const CACHE_KEY = 'hms_settings_map_v2';

    public function all(bool $refresh = false): array
    {
        return $this->nonSensitive($this->cached($refresh));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->cached()->get($key);

        if ($setting === null) {
            return $default;
        }

        return $this->castValue($setting);
    }

    public function has(string $key): bool
    {
        return $this->cached()->has($key);
    }

    public function set(string $key, mixed $value, ?string $group = null, ?string $description = null): Setting
    {
        $existing = Setting::where('key', $key)->first();

        if ($existing && $existing->is_locked) {
            return $existing;
        }

        $type = $this->detectType($value, $existing?->type);
        $serialized = $this->serialize($value, $type);

        $payload = [
            'group' => $group ?? $existing?->group ?? $this->groupFor($key),
            'value' => $serialized,
            'type' => $type,
            'is_sensitive' => $existing?->is_sensitive ?? false,
            'is_locked' => $existing?->is_locked ?? false,
        ];

        if ($description !== null) {
            $payload['description'] = $description;
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            $payload
        );

        $this->flush();

        return $setting;
    }

    public function setMany(iterable $values, ?string $group = null): void
    {
        DB::transaction(function () use ($values, $group) {
            foreach ($values as $key => $value) {
                $this->set((string) $key, $value, $group);
            }
        });
    }

    public function defaults(): array
    {
        return [
            ['group' => 'system',     'key' => 'system.app_name',           'value' => config('app.name'),          'type' => 'string', 'description' => 'Application name'],
            ['group' => 'system',     'key' => 'system.locale',             'value' => config('app.locale'),        'type' => 'string', 'description' => 'Default application locale'],
            ['group' => 'system',     'key' => 'system.fallback_locale',    'value' => config('app.fallback_locale'), 'type' => 'string'],
            ['group' => 'system',     'key' => 'system.timezone',           'value' => config('app.timezone'),       'type' => 'string', 'description' => 'Default application timezone'],
            ['group' => 'system',     'key' => 'system.currency',           'value' => 'USD',                       'type' => 'string', 'description' => 'Default currency code'],
            ['group' => 'system',     'key' => 'system.date_format',        'value' => 'Y-m-d',                     'type' => 'string'],
            ['group' => 'system',     'key' => 'system.time_format',        'value' => 'H:i',                       'type' => 'string'],

            ['group' => 'hospital',   'key' => 'hospital.name',             'value' => 'HealthNexus',               'type' => 'string'],
            ['group' => 'hospital',   'key' => 'hospital.phone',            'value' => null,                        'type' => 'string'],
            ['group' => 'hospital',   'key' => 'hospital.email',            'value' => null,                        'type' => 'string'],
            ['group' => 'hospital',   'key' => 'hospital.address',          'value' => null,                        'type' => 'string'],

            ['group' => 'localization', 'key' => 'localization.supported_locales', 'value' => json_encode(['en' => 'English', 'bn' => 'Bangla', 'ar' => 'Arabic']), 'type' => 'json'],

            ['group' => 'security',   'key' => 'security.session_timeout',       'value' => '120',                       'type' => 'integer'],
            ['group' => 'security',   'key' => 'security.max_login_attempts',    'value' => '5',                         'type' => 'integer'],
            ['group' => 'security',   'key' => 'security.password_expiry_days',  'value' => '90',                        'type' => 'integer'],
            ['group' => 'security',   'key' => 'security.password_min_length',   'value' => '8',                         'type' => 'integer', 'description' => 'Minimum password length', 'is_sensitive' => true],

            ['group' => 'notifications', 'key' => 'notifications.email_enabled',  'value' => '1',                         'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'notifications.sms_enabled',   'value' => '0',                         'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'notifications.whatsapp_enabled', 'value' => '0',                         'type' => 'boolean'],

            ['group' => 'files',      'key' => 'files.max_size_kb',           'value' => '2048',                      'type' => 'integer'],
            ['group' => 'files',      'key' => 'files.allowed_extensions',    'value' => json_encode(['pdf', 'jpg', 'png', 'docx']), 'type' => 'json'],

            ['group' => 'audit',      'key' => 'audit.retention_days',        'value' => '365',                       'type' => 'integer'],

            ['group' => 'billing',    'key' => 'billing.currency',                            'value' => 'BDT',   'type' => 'string', 'description' => 'Default billing currency'],
            ['group' => 'billing',    'key' => 'billing.invoice_prefix',                      'value' => 'INV',   'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.payment_prefix',                      'value' => 'PMT',   'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.receipt_prefix',                      'value' => 'RCT',   'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.refund_prefix',                       'value' => 'RFD',   'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.adjustment_prefix',                    'value' => 'ADJ',   'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.invoice_number_format',                'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string', 'description' => 'Document numbering format'],
            ['group' => 'billing',    'key' => 'billing.rounding_precision',                   'value' => '2',     'type' => 'integer'],
            ['group' => 'billing',    'key' => 'billing.rounding_mode',                        'value' => 'nearest', 'type' => 'string', 'description' => 'nearest|up|down'],
            ['group' => 'billing',    'key' => 'billing.discount_approval_threshold_percent',  'value' => '10',    'type' => 'integer', 'description' => 'Discount % above which approval is required'],
            ['group' => 'billing',    'key' => 'billing.discount_approval_threshold_amount',   'value' => '5000',  'type' => 'integer', 'description' => 'Discount amount above which approval is required'],
            ['group' => 'billing',    'key' => 'billing.discount_approver_role',               'value' => 'hospital_admin', 'type' => 'string'],
            ['group' => 'billing',    'key' => 'billing.refund_approval_required',             'value' => '1',     'type' => 'boolean'],
            ['group' => 'billing',    'key' => 'billing.default_price_list_priority',          'value' => '100',   'type' => 'integer'],
            ['group' => 'billing',    'key' => 'billing.tax_inclusive_default',                'value' => '0',     'type' => 'boolean'],
            ['group' => 'billing',    'key' => 'billing.advance_min_balance',                  'value' => '0',     'type' => 'integer'],

            ['group' => 'laboratory', 'key' => 'laboratory.order_prefix',                      'value' => 'LAB',   'type' => 'string'],
            ['group' => 'laboratory', 'key' => 'laboratory.accession_prefix',                   'value' => 'ACC',   'type' => 'string'],
            ['group' => 'laboratory', 'key' => 'laboratory.report_prefix',                      'value' => 'RPT',   'type' => 'string'],
            ['group' => 'laboratory', 'key' => 'laboratory.order_number_format',                'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string', 'description' => 'Document numbering format'],
            ['group' => 'laboratory', 'key' => 'laboratory.accession_number_format',             'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string'],
            ['group' => 'laboratory', 'key' => 'laboratory.default_turnaround_minutes',          'value' => '60',    'type' => 'integer'],
            ['group' => 'laboratory', 'key' => 'laboratory.critical_notification_channels',      'value' => json_encode(['database', 'mail']), 'type' => 'json'],
            ['group' => 'laboratory', 'key' => 'laboratory.require_pathologist_approval_default', 'value' => '0',    'type' => 'boolean'],

            ['group' => 'radiology', 'key' => 'radiology.order_prefix',                          'value' => 'RAD',   'type' => 'string'],
            ['group' => 'radiology', 'key' => 'radiology.accession_prefix',                       'value' => 'RAD',   'type' => 'string'],
            ['group' => 'radiology', 'key' => 'radiology.order_number_format',                    'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string'],
            ['group' => 'radiology', 'key' => 'radiology.accession_number_format',                 'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string'],
            ['group' => 'radiology', 'key' => 'radiology.default_turnaround_minutes',              'value' => '120',   'type' => 'integer'],
            ['group' => 'radiology', 'key' => 'radiology.critical_finding_notification_channels',  'value' => json_encode(['database', 'mail']), 'type' => 'json'],
            ['group' => 'radiology', 'key' => 'radiology.dicom_uid_root',                          'value' => '1.2.826.0.1.3680043.10.001', 'type' => 'string', 'description' => 'Org root OID used only if the app ever pre-assigns a Study Instance UID'],
            ['group' => 'radiology', 'key' => 'radiology.default_pacs_server_id',                  'value' => null,    'type' => 'integer'],

            ['group' => 'pharmacy', 'key' => 'pharmacy.order_prefix',                        'value' => 'RX',    'type' => 'string'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.dispensing_prefix',                    'value' => 'DSP',   'type' => 'string'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.transfer_prefix',                       'value' => 'TRF',   'type' => 'string'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.return_prefix',                         'value' => 'RTN',   'type' => 'string'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.order_number_format',                    'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.near_expiry_threshold_days',             'value' => '90',    'type' => 'integer', 'description' => 'Batches expiring within this many days are flagged near-expiry'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.allow_negative_stock',                   'value' => '0',     'type' => 'boolean', 'description' => 'Reserved — no override path is wired in Phase 7; dispensing always refuses insufficient stock'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.controlled_drug_witness_required',        'value' => '0',     'type' => 'boolean'],
            ['group' => 'pharmacy', 'key' => 'pharmacy.substitution_requires_approval',          'value' => '1',     'type' => 'boolean'],

            ['group' => 'ipd', 'key' => 'ipd.admission_prefix',                    'value' => 'ADM',   'type' => 'string'],
            ['group' => 'ipd', 'key' => 'ipd.admission_number_format',              'value' => '{PREFIX}-{YEAR}-{SEQ:8}', 'type' => 'string'],
            ['group' => 'ipd', 'key' => 'ipd.bed_reservation_expiry_minutes',       'value' => '120',   'type' => 'integer', 'description' => 'Minutes after which an unconverted bed reservation expires and the bed returns to Available'],
            ['group' => 'ipd', 'key' => 'ipd.discharge_requires_cleaning',          'value' => '1',     'type' => 'boolean', 'description' => 'When true, a released bed goes to Cleaning rather than directly to Available'],
            ['group' => 'ipd', 'key' => 'ipd.leave_default_bed_handling',           'value' => 'retain', 'type' => 'string', 'description' => 'retain keeps the bed allocation active during patient leave; release frees the bed'],
            ['group' => 'ipd', 'key' => 'ipd.admission_requires_approval',          'value' => '1',     'type' => 'boolean'],
            ['group' => 'ipd', 'key' => 'ipd.delayed_discharge_escalation_hours',   'value' => '24',    'type' => 'integer'],

            ['group' => 'nursing', 'key' => 'nursing.mar_frequency_intervals',      'value' => json_encode(['OD' => 24, 'BID' => 12, 'TID' => 8, 'QID' => 6, 'Q4H' => 4, 'Q6H' => 6, 'Q8H' => 8, 'Q12H' => 12]), 'type' => 'json', 'description' => 'Hospital-configurable map of recognized frequency codes to hour intervals, used only to auto-generate the next scheduled MAR dose — unrecognized codes and all PRN items fall back to nurse-initiated scheduling'],
            ['group' => 'nursing', 'key' => 'nursing.vitals_default_frequency_minutes', 'value' => '240',  'type' => 'integer', 'description' => 'Default interval between routine vital-sign observations when no care plan/order specifies otherwise'],
            ['group' => 'nursing', 'key' => 'nursing.handover_requires_acknowledgement', 'value' => '1',   'type' => 'boolean'],
            ['group' => 'nursing', 'key' => 'nursing.high_alert_requires_witness',   'value' => '1',     'type' => 'boolean', 'description' => 'Requires a second-nurse witness for administration of medications flagged is_high_alert in Phase 7'],
            ['group' => 'nursing', 'key' => 'nursing.controlled_requires_witness',   'value' => '1',     'type' => 'boolean', 'description' => 'Requires a second-nurse witness for administration of medications flagged is_controlled in Phase 7'],
            ['group' => 'nursing', 'key' => 'nursing.prn_reassessment_minutes',      'value' => '60',    'type' => 'integer', 'description' => 'Minutes after a PRN administration by which a reassessment/response should be documented'],
            ['group' => 'nursing', 'key' => 'nursing.escalation_default_recipient_role', 'value' => 'charge_nurse', 'type' => 'string'],
            ['group' => 'nursing', 'key' => 'nursing.observation_thresholds', 'value' => json_encode(['blood_glucose' => ['low' => 70, 'high' => 200], 'pain' => ['low' => null, 'high' => 7]]), 'type' => 'json', 'description' => 'Hospital-configurable low/high alert thresholds per nursing_observations.observation_type — a breach raises a NursingAlert, never a diagnosis'],

            ['group' => 'patients',   'key' => 'patients.duplicate_weights', 'value' => json_encode(['name' => 30, 'date_of_birth' => 25, 'phone' => 20, 'national_identifier' => 20, 'email' => 5]), 'type' => 'json', 'description' => 'Weighted duplicate-detection scoring per matched field'],
            // Deliberately excludes first_name/last_name — routine typo corrections are common
            // and would make the approval workflow a burden; only fields where a change is rare
            // and identity/clinical-safety sensitive require approval.
            ['group' => 'patients',   'key' => 'patients.amendment_sensitive_fields', 'value' => json_encode(['date_of_birth', 'sex', 'national_identifier']), 'type' => 'json', 'description' => 'Fields that require the amendment approval workflow instead of a direct edit'],
        ];
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function cached(bool $refresh = false): Collection
    {
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $rows = Cache::remember(self::CACHE_KEY, 3600, fn () => Setting::all()->map(fn (Setting $s) => $s->getAttributes())->all());

        return Setting::hydrate($rows)->keyBy('key');
    }

    protected function nonSensitive(Collection $collection): array
    {
        return $collection
            ->filter(fn (Setting $s) => ! $s->is_sensitive)
            ->sortBy(fn (Setting $s) => [$s->group, $s->sort_order, $s->key])
            ->groupBy('group')
            ->mapWithKeys(function (Collection $group, string $name) {
                $prefix = $name.'.';

                return [
                    $name => $group->mapWithKeys(function (Setting $s) use ($prefix) {
                        $key = str_starts_with($s->key, $prefix)
                            ? substr($s->key, strlen($prefix))
                            : $s->key;

                        return [$key => $this->castValue($s)];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    protected function castValue(Setting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => $setting->value ? json_decode($setting->value, true) : null,
            default => $setting->value,
        };
    }

    protected function detectType(mixed $value, ?string $existing = null): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_array($value)) {
            return 'json';
        }

        return $existing ?? 'string';
    }

    protected function serialize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => $value === null ? null : json_encode($value),
            default => is_string($value) ? $value : json_encode($value),
        };
    }

    protected function groupFor(string $key): string
    {
        return explode('.', $key)[0];
    }
}
