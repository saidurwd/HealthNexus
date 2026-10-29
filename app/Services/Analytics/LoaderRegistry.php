<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Loaders\Loader;

/** The loaders in the order they must run: calendar and other dimensions first, then the facts that point at them. */
class LoaderRegistry
{
    /** @var list<class-string<Loader>> */
    public const LOADERS = [
        Loaders\DateDimension::class, Loaders\BranchDimension::class, Loaders\DepartmentDimension::class, Loaders\ProviderDimension::class, Loaders\PatientDimension::class,
        Loaders\EncounterFact::class, Loaders\AppointmentFact::class, Loaders\InvoiceFact::class, Loaders\RevenueLineFact::class, Loaders\PaymentFact::class,
        Loaders\LabOrderFact::class, Loaders\RadiologyExamFact::class, Loaders\DispensingFact::class,
        Loaders\AdmissionFact::class, Loaders\BedCensusFact::class, Loaders\SurgeryFact::class, Loaders\IcuStayFact::class, Loaders\EdVisitFact::class,
        Loaders\ClaimFact::class, Loaders\StockDailyFact::class, Loaders\DailyMetricsFact::class, Loaders\LedgerMonthlyFact::class,
    ];

    /** @return list<Loader> */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), static::LOADERS);
    }

    public function find(string $key): ?Loader
    {
        foreach ($this->all() as $loader) {
            if ($loader->key() === $key) {
                return $loader;
            }
        }

        return null;
    }
}
