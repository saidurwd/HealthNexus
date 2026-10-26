<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodUnit;
use App\Services\Blood\BloodDonationService;
use App\Services\Blood\BloodDonorService;
use App\Services\Blood\BloodMasterData;
use App\Services\Blood\BloodProcessingService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Illuminate\Validation\ValidationException;

/**
 * Donor side, first half: donors register and are screened (some deferred), donate, bags are separated into components and their tests are ordered
 * from the Laboratory. The Laboratory history then runs those orders; BloodTransfusionHistory later releases and uses the bags.
 */
class BloodDonationsHistory implements DemoHistoryModule
{
    /** ABO/Rh mix of the donor population (EXAMPLE shares for demo data only). */
    private const GROUPS = [['O', 'positive', 36], ['B', 'positive', 28], ['A', 'positive', 22], ['AB', 'positive', 6], ['O', 'negative', 3], ['B', 'negative', 2], ['A', 'negative', 2], ['AB', 'negative', 1]];

    public function __construct(
        private readonly BloodDonorService $donors,
        private readonly BloodDonationService $donations,
        private readonly BloodProcessingService $processing,
        private readonly BloodMasterData $masters,
    ) {}

    public function name(): string
    {
        return 'Blood bank — donors';
    }

    public function module(): string
    {
        return 'bloodbank';
    }

    public function run(HistoryContext $context): string
    {
        $this->masters->ensure($context->organization->id);
        $perDay = max(1, intdiv($context->visitsPerDay, 4));
        $groups = []; // donor id => [abo, rh]
        $donationGroups = []; // donation id => [abo, rh]  (what the lab will report)
        $counts = ['donors' => 0, 'donations' => 0, 'deferred' => 0, 'reactions' => 0];
        $pool = [];

        foreach ($context->days() as $day) {
            for ($n = 0; $n < mt_rand(max(0, $perDay - 1), $perDay + 1); $n++) {
                $at = $context->workTime($day, $n * 40);
                $donor = $this->pickDonor($context, $pool, $at, $counts);
                $staff = $context->staff('blood_bank_technician')->first();

                $screening = $context->at($at, fn () => $this->donors->screen($donor->refresh(), $staff, $this->vitals($context)));
                if ($screening->outcome !== 'eligible') {
                    $counts['deferred']++;

                    continue;
                }
                try {
                    $donation = $context->at($at->addMinutes(10), fn () => $this->donations->start($donor->refresh(), $screening, $context->branch, $staff, 'LOT-'.$at->format('ym')));
                    $context->at($at->addMinutes(25), fn () => $this->donations->complete($donation, mt_rand(430, 470), $staff));
                } catch (ValidationException) {
                    continue;
                }
                $counts['donations']++;
                $context->at($at->addMinutes(30), function () use ($donation, $staff, $context, &$counts) {
                    $this->donations->finishObservation($donation->refresh());
                    if ($context->chance(2)) {
                        $this->donations->recordAdverseEvent($donation, $staff, 'vasovagal', 'mild', 'Felt faint after donating', 'Lay down, given fluids');
                        $counts['reactions']++;
                    }
                    $whole = BloodUnit::query()->where('donation_id', $donation->id)->where('component', 'whole_blood')->firstOrFail();
                    $this->processing->separate($whole, $staff);
                });
                $groups[$donor->id] ??= $this->group($context);
                $donationGroups[$donation->id] = $groups[$donor->id];
            }
        }

        $context->share('blood_groups', $donationGroups);

        return sprintf('%d donors, %d donations (%d screenings deferred), %d donor reactions', $counts['donors'], $counts['donations'], $counts['deferred'], $counts['reactions']);
    }

    /** @param  array<int, BloodDonor>  $pool */
    private function pickDonor(HistoryContext $context, array &$pool, $at, array &$counts): BloodDonor
    {
        $eligible = array_values(array_filter($pool, fn (BloodDonor $d) => ! $d->refresh()->isDeferred() && (! $d->last_donation_at || $d->last_donation_at->lt($at->subDays(125)))));
        if ($eligible !== [] && $context->chance(65)) {
            return $context->pick($eligible);
        }

        $staff = $context->staff('blood_bank_technician')->first();
        /** @var BloodDonor $donor */
        $donor = $context->at($at->subMinutes(20), function () use ($context, $staff) {
            $donor = $this->donors->register($staff, $context->branch, [
                'first_name' => $context->pick(['Rahim', 'Karim', 'Jamal', 'Nasrin', 'Fatema', 'Hasan', 'Ayesha', 'Imran', 'Salma', 'Tanvir']), 'last_name' => $context->pick(['Uddin', 'Rahman', 'Hossain', 'Akter', 'Khan', 'Islam', 'Ahmed']),
                'sex' => $context->chance(75) ? 'M' : 'F', 'date_of_birth' => now()->subYears(mt_rand(19, 55))->subDays(mt_rand(0, 300))->toDateString(), 'phone' => sprintf('0181%07d', mt_rand(1000000, 9999999)),
            ], $context->chance(30));

            return $this->donors->recordConsent($donor, $staff);
        });
        $counts['donors']++;
        $pool[] = $donor;

        return $donor;
    }

    /** @return array<string, mixed> mostly passing, sometimes a low haemoglobin */
    private function vitals(HistoryContext $context): array
    {
        return ['weight_kg' => mt_rand(52, 90), 'hemoglobin' => $context->chance(8) ? 11.0 : round(13.2 + mt_rand(0, 25) / 10, 1), 'systolic' => mt_rand(105, 140), 'diastolic' => mt_rand(65, 90), 'pulse' => mt_rand(60, 92), 'temperature_c' => 36.6, 'questionnaire' => []];
    }

    /** @return array{0: string, 1: string} */
    private function group(HistoryContext $context): array
    {
        $roll = mt_rand(1, 100);
        $sum = 0;
        foreach (self::GROUPS as [$abo, $rh, $share]) {
            $sum += $share;
            if ($roll <= $sum) {
                return [$abo, $rh];
            }
        }

        return ['O', 'positive'];
    }
}
