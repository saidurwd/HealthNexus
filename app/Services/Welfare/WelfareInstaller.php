<?php

namespace App\Services\Welfare;

use App\Models\Welfare\WfFund;

/** What a hospital starts with: three example funds the welfare committee will rename and fill. Safe to run again. */
class WelfareInstaller
{
    public function __construct(private readonly FundService $funds) {}

    /** @return array{funds:int} */
    public function install(int $organizationId): array
    {
        $n = 0;
        foreach ([['Zakat fund', 'zakat', 'Zakat given to the hospital for treating people who cannot pay. Use only as the giver and your scholars allow.', 20000, 50000], ['General donations', 'donation', 'Gifts from individuals and companies for any patient in need.', 15000, 30000],
            ['Hospital charity fund', 'hospital', 'The hospital\'s own budget for free and reduced-price care.', 10000, 100000]] as [$name, $kind, $about, $cap, $alert]) {
            if (! WfFund::query()->where(['organization_id' => $organizationId, 'name' => $name])->exists()) {
                $this->funds->createFund($organizationId, ['name' => $name, 'kind' => $kind, 'description' => $about, 'per_grant_cap' => $cap, 'low_balance_alert' => $alert]);
                $n++;
            }
        }

        return ['funds' => $n];
    }
}
