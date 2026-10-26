<?php

namespace App\Services\Demo\History;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Shared state for one demo-history run: who, where, when, and the records earlier modules left for later ones to build on. */
final class HistoryContext
{
    /** @var array<string, mixed> things one module hands to the next (e.g. 'encounters' => list of ids) */
    private array $shared = [];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public function __construct(
        public readonly Organization $organization,
        public readonly Branch $branch,
        public readonly User $actor,
        public readonly int $months = 6,
        public readonly int $visitsPerDay = 8,
        public readonly int $seed = 2026,
    ) {
        $this->to = CarbonImmutable::now()->startOfDay()->subDay();
        $this->from = $this->to->subMonthsNoOverflow($months)->addDay();
        mt_srand($seed);
    }

    /** Runs $callback with the application clock set to $moment, so every service stamps its records at that time. */
    public function at(\DateTimeInterface $moment, callable $callback): mixed
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow(Carbon::instance($moment));
        try {
            return $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }

    /** @return iterable<CarbonImmutable> every calendar day of the history, oldest first */
    public function days(): iterable
    {
        for ($day = $this->from; $day->lte($this->to); $day = $day->addDay()) {
            yield $day;
        }
    }

    /** A time during the working day (08:00–19:59) on $day. */
    public function workTime(CarbonImmutable $day, int $minuteOffset = 0): CarbonImmutable
    {
        return $day->setTime(8, 0)->addMinutes(($minuteOffset + mt_rand(0, 8) * 15) % 720);
    }

    public function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /** @template T @param  array<int, T>|Collection<int, T>  $items @return T */
    public function pick(array|Collection $items): mixed
    {
        $items = $items instanceof Collection ? $items->values()->all() : array_values($items);

        return $items[mt_rand(0, count($items) - 1)];
    }

    /** @return Collection<int, User> demo staff with the role, in this organization; falls back to the actor when there is none */
    public function staff(string $role): Collection
    {
        $users = User::query()->role($role)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organization->id))->orderBy('id')->get();

        return $users->isEmpty() ? collect([$this->actor]) : $users;
    }

    /** @return Collection<int, Patient> */
    public function patients(): Collection
    {
        return Patient::query()->where('organization_id', $this->organization->id)->orderBy('id')->get();
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function shared(string $key, mixed $default = []): mixed
    {
        return $this->shared[$key] ?? $default;
    }
}
