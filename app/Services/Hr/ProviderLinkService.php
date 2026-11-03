<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrProviderLink;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * D-12: a provider is a role a person plays, an employee is the person employed. This proposes the pairs — by login, by licence number, by name —
 * and a person decides each one on a review screen. Nothing is ever linked automatically, and a provider with several possible people is shown as such.
 */
class ProviderLinkService
{
    /** @return int links proposed */
    public function propose(int $organizationId): int
    {
        $count = 0;
        $employees = HrEmployee::query()->where('organization_id', $organizationId)->with('credentials')->get();
        $linkedProviders = Provider::query()->where('organization_id', $organizationId)->whereNotNull('employee_id')->pluck('id')->all();
        Provider::query()->where('organization_id', $organizationId)->whereNull('employee_id')->whereNotIn('id', $linkedProviders)->orderBy('id')->each(function (Provider $provider) use ($employees, &$count, $organizationId) {
            foreach ($employees as $employee) {
                [$score, $why] = $this->score($provider, $employee);
                if ($score < 60) {
                    continue;
                }
                $link = HrProviderLink::query()->firstOrCreate(['provider_id' => $provider->id, 'employee_id' => $employee->id], ['organization_id' => $organizationId, 'score' => min(100, $score), 'reasons' => implode(', ', $why), 'status' => 'pending']);
                $link->wasRecentlyCreated && $count++;
            }
        });

        return $count;
    }

    /** @return array{0: int, 1: list<string>} */
    private function score(Provider $provider, HrEmployee $employee): array
    {
        $score = 0;
        $why = [];
        if ($provider->user_id && $employee->user_id && $provider->user_id === $employee->user_id) {
            $score += 70;
            $why[] = 'same login';
        }
        if ($provider->license_number && $employee->credentials->contains(fn ($c) => $c->number && $this->clean($c->number) === $this->clean($provider->license_number))) {
            $score += 60;
            $why[] = 'same licence number';
        }
        if ($this->name($provider->name) === $this->name($employee->fullName()) && $this->name($provider->name) !== '') {
            $score += 60;
            $why[] = 'same name';
        }
        if ($score > 0 && $provider->department_id && $provider->department_id === $employee->department_id) {
            $score += 10;
            $why[] = 'same department';
        }

        return [$score, $why];
    }

    /** Pending links grouped by provider, each with its competing candidates. */
    public function review(int $organizationId)
    {
        return HrProviderLink::query()->with(['provider', 'employee'])->where('organization_id', $organizationId)->where('status', 'pending')->orderBy('provider_id')->orderByDesc('score')->get()
            ->groupBy('provider_id')->map(fn ($group) => ['provider' => $group->first()->provider, 'candidates' => $group, 'ambiguous' => $group->count() > 1 || HrProviderLink::where('employee_id', $group->first()->employee_id)->where('status', 'pending')->count() > 1]);
    }

    public function accept(HrProviderLink $link, User $by): HrProviderLink
    {
        return DB::transaction(function () use ($link, $by) {
            $link = HrProviderLink::query()->lockForUpdate()->findOrFail($link->id);
            if ($link->status !== 'pending') {
                throw ValidationException::withMessages(['link' => "This suggestion is already {$link->status}."]);
            }
            $provider = Provider::query()->lockForUpdate()->findOrFail($link->provider_id);
            if ($provider->employee_id && $provider->employee_id !== $link->employee_id) {
                throw ValidationException::withMessages(['link' => 'That provider is already linked to someone else.']);
            }
            $employee = HrEmployee::findOrFail($link->employee_id);
            $provider->update(['employee_id' => $employee->id, 'status' => in_array($employee->status, ['resigned', 'terminated', 'retired'], true) ? 'inactive' : $provider->status]);
            $link->update(['status' => 'accepted', 'reviewed_by' => $by->id, 'reviewed_at' => now()]);
            HrProviderLink::query()->where('provider_id', $provider->id)->where('id', '!=', $link->id)->where('status', 'pending')->update(['status' => 'rejected', 'reviewed_by' => $by->id, 'reviewed_at' => now()]);

            return $link->refresh();
        });
    }

    public function reject(HrProviderLink $link, User $by): HrProviderLink
    {
        if ($link->status !== 'pending') {
            throw ValidationException::withMessages(['link' => "This suggestion is already {$link->status}."]);
        }
        $link->update(['status' => 'rejected', 'reviewed_by' => $by->id, 'reviewed_at' => now()]);

        return $link->refresh();
    }

    private function name(string $name): string
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/\b(dr|prof|mr|mrs|ms|md|md\.)\b\.?/u', ' ', $name) ?? $name;

        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? '');
    }

    private function clean(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', mb_strtolower($value)) ?? '';
    }
}
