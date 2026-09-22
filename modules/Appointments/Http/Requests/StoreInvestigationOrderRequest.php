<?php

namespace Modules\Appointments\Http\Requests;

use App\Services\TenantContextResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreInvestigationOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'test_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'clinical_notes' => ['nullable', 'string'],
            'priority' => ['in:routine,urgent,stat'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(TenantContextResolver::class)->getBranchId();
        $data['order_no'] = 'LAB-'.strtoupper(Str::random(8));

        return $data;
    }
}
