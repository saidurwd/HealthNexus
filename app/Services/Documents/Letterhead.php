<?php

namespace App\Services\Documents;

use App\Models\Branch;

/** Letter-head block for a branch: hospital settings first, organization as fallback (guideline 14.3). */
class Letterhead
{
    public function for(?Branch $branch): array
    {
        $branch?->loadMissing('hospital', 'organization');
        $hospital = $branch?->hospital;
        $organization = $branch?->organization;
        $settings = $hospital?->settings['letterhead'] ?? [];

        return [
            'name' => $settings['name'] ?? $hospital?->name ?? $organization?->name ?? '',
            'name_bn' => $settings['name_bn'] ?? '',
            'address' => $settings['address'] ?? $branch?->address ?? $hospital?->address ?? $organization?->address ?? '',
            'phone' => $settings['phone'] ?? $branch?->phone ?? $hospital?->phone ?? $organization?->phone ?? '',
            'license_number' => $hospital?->license_number ?? '',
            'vat_registration' => $settings['vat_registration'] ?? '',
        ];
    }
}
