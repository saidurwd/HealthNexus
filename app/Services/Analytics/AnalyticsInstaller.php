<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Kpi\DashboardService;
use App\Services\Analytics\Kpi\KpiService;

/** Puts the standard content in place: the KPI catalogue, the five role dashboards and the data-quality rules. Safe to run again. */
class AnalyticsInstaller
{
    public function __construct(private readonly KpiService $kpis, private readonly DashboardService $dashboards, private readonly DataQualityService $quality) {}

    /** @return array{kpis:int,dashboards:int,rules:int} */
    public function install(): array
    {
        return ['kpis' => $this->kpis->installStandard(), 'dashboards' => $this->dashboards->installSystem(), 'rules' => $this->quality->installRules()];
    }
}
