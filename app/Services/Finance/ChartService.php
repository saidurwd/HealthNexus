<?php

namespace App\Services\Finance;

use App\Models\Billing\BillingCategory;
use App\Models\Finance\Account;
use App\Models\Finance\AccountRole;
use App\Models\Finance\CostCenter;
use App\Models\Finance\JournalLine;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** The chart of accounts, cost centres and the account roles that tell the posting engine where things go. */
class ChartService
{
    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public const CONTROLS = ['receivable', 'payable', 'bank', 'cash', 'inventory', 'tax'];

    public function __construct(private readonly AuditLogger $audit) {}

    public static function normalSide(string $type): string
    {
        return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    /** Make the starter chart, roles and a default cost centre once; running it again changes nothing that exists. @return array{accounts:int,roles:int} */
    public function seedStarter(int $organizationId): array
    {
        return DB::transaction(function () use ($organizationId) {
            $made = ['accounts' => 0, 'roles' => 0];
            $byCode = [];
            foreach ((array) config('finance.starter_chart') as $row) {
                [$code, $name, $type, $parent, $postable, $control] = array_pad($row, 6, null);
                $account = Account::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $code], [
                    'name' => $name, 'type' => $type, 'normal_side' => self::normalSide($type), 'parent_id' => $parent ? ($byCode[$parent] ?? Account::query()->where(['organization_id' => $organizationId, 'code' => $parent])->value('id')) : null,
                    'postable' => $postable, 'control' => $control ?? null, 'system' => true,
                ]);
                $account->wasRecentlyCreated && $made['accounts']++;
                $byCode[$code] = $account->id;
            }
            foreach ((array) config('finance.starter_roles') as $role => $scopes) {
                foreach ($scopes as $scope => $code) {
                    $row = AccountRole::query()->firstOrCreate(['organization_id' => $organizationId, 'role' => $role, 'scope_key' => (string) $scope], ['account_id' => $byCode[$code] ?? Account::query()->where(['organization_id' => $organizationId, 'code' => $code])->value('id')]);
                    $row->wasRecentlyCreated && $made['roles']++;
                }
            }
            $made['roles'] += $this->mapBillingCategories($organizationId, $byCode);
            CostCenter::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => 'HOSP'], ['name' => 'Whole hospital', 'kind' => 'profit']);

            return $made;
        });
    }

    /** @param array<string,int> $byCode */
    private function mapBillingCategories(int $organizationId, array $byCode): int
    {
        $n = 0;
        foreach (BillingCategory::query()->where('organization_id', $organizationId)->get() as $category) {
            $haystack = Str::lower($category->code.' '.$category->name.' ');
            foreach ((array) config('finance.revenue_keywords') as $accountCode => $words) {
                if (Str::contains($haystack, $words) && isset($byCode[$accountCode])) {
                    $row = AccountRole::query()->firstOrCreate(['organization_id' => $organizationId, 'role' => 'revenue', 'scope_key' => (string) $category->code], ['account_id' => $byCode[$accountCode]]);
                    $row->wasRecentlyCreated && $n++;
                    break;
                }
            }
        }

        return $n;
    }

    /** @param array{code:string,name:string,type:string,parent_id?:?int,postable?:bool,control?:?string,note?:?string} $d */
    public function createAccount(int $organizationId, array $d): Account
    {
        $this->validate($organizationId, $d);
        $account = Account::create(['organization_id' => $organizationId, 'code' => $d['code'], 'name' => $d['name'], 'type' => $d['type'], 'normal_side' => self::normalSide($d['type']), 'parent_id' => $d['parent_id'] ?? null,
            'postable' => $d['postable'] ?? true, 'control' => $d['control'] ?? null, 'note' => $d['note'] ?? null]);
        $this->audit->log('FIN_ACCOUNT_CREATED', Account::class, $account->id, null, ['code' => $account->code, 'name' => $account->name]);

        return $account;
    }

    /** @param array<string,mixed> $d */
    public function updateAccount(Account $account, array $d): Account
    {
        $used = JournalLine::query()->where('account_id', $account->id)->exists();
        if ($used && isset($d['type']) && $d['type'] !== $account->type) {
            throw ValidationException::withMessages(['type' => 'An account that has postings cannot change its type.']);
        }
        if ($used && isset($d['postable']) && ! $d['postable']) {
            throw ValidationException::withMessages(['postable' => 'An account that has postings cannot become a heading.']);
        }
        $this->validate($account->organization_id, $d + ['code' => $account->code, 'name' => $account->name, 'type' => $account->type], $account);
        $old = $account->only(['code', 'name', 'type', 'active']);
        $account->fill(array_intersect_key($d, array_flip(['code', 'name', 'type', 'parent_id', 'postable', 'control', 'note', 'active'])));
        $account->normal_side = self::normalSide($account->type);
        $account->save();
        $this->audit->log('FIN_ACCOUNT_UPDATED', Account::class, $account->id, $old, $account->only(['code', 'name', 'type', 'active']));

        return $account;
    }

    /** @param array<string,mixed> $d */
    private function validate(int $organizationId, array $d, ?Account $existing = null): void
    {
        $errors = [];
        $code = trim((string) ($d['code'] ?? ''));
        if ($code === '' || mb_strlen($code) > 20) {
            $errors['code'] = 'Give the account a code of up to 20 characters.';
        } elseif (Account::query()->where(['organization_id' => $organizationId, 'code' => $code])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            $errors['code'] = 'That account code is already used.';
        }
        if (trim((string) ($d['name'] ?? '')) === '') {
            $errors['name'] = 'Give the account a name.';
        }
        if (! in_array($d['type'] ?? null, self::TYPES, true)) {
            $errors['type'] = 'Choose asset, liability, equity, revenue or expense.';
        }
        if (! empty($d['control']) && ! in_array($d['control'], self::CONTROLS, true)) {
            $errors['control'] = 'Unknown control kind.';
        }
        if (! empty($d['parent_id'])) {
            $parent = Account::query()->where('organization_id', $organizationId)->find($d['parent_id']);
            if ($parent === null) {
                $errors['parent_id'] = 'The parent account does not exist.';
            } elseif ($parent->type !== ($d['type'] ?? $parent->type)) {
                $errors['parent_id'] = 'A parent and its children must be of the same type.';
            } elseif ($existing && $this->isUnder($parent, $existing)) {
                $errors['parent_id'] = 'An account cannot sit under itself.';
            } elseif ($parent->postable && JournalLine::query()->where('account_id', $parent->id)->exists()) {
                $errors['parent_id'] = 'That account already has postings; choose a heading as the parent.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isUnder(Account $candidate, Account $ancestor): bool
    {
        for ($i = 0; $candidate && $i < 20; $i++) {
            if ($candidate->id === $ancestor->id) {
                return true;
            }
            $candidate = $candidate->parent_id ? Account::query()->find($candidate->parent_id) : null;
        }

        return false;
    }

    /** An account with postings is switched off, never deleted; only an unused, non-system account can be removed. */
    public function deleteAccount(Account $account): void
    {
        if ($account->system || JournalLine::query()->where('account_id', $account->id)->exists() || AccountRole::query()->where('account_id', $account->id)->exists() || Account::query()->where('parent_id', $account->id)->exists()) {
            throw ValidationException::withMessages(['account' => 'This account is in use or was made by the starter chart. Switch it off instead.']);
        }
        $account->delete();
        $this->audit->log('FIN_ACCOUNT_DELETED', Account::class, $account->id, ['code' => $account->code], null);
    }

    public function setRole(int $organizationId, string $role, string $scope, int $accountId): AccountRole
    {
        $account = Account::query()->where('organization_id', $organizationId)->where('active', true)->where('postable', true)->find($accountId);
        if ($account === null) {
            throw ValidationException::withMessages(['account_id' => 'Choose an active account that takes postings.']);
        }

        return AccountRole::query()->updateOrCreate(['organization_id' => $organizationId, 'role' => $role, 'scope_key' => $scope], ['account_id' => $account->id]);
    }

    /** The account for a role, falling back to the role's "all" row. A missing mapping is an error the accountant can fix, never a silent guess. */
    public function accountFor(int $organizationId, string $role, ?string $scope = null): Account
    {
        $rows = AccountRole::query()->where(['organization_id' => $organizationId, 'role' => $role])->whereIn('scope_key', array_filter([(string) $scope, 'all']))->with('account')->get();
        $row = $rows->firstWhere('scope_key', (string) $scope) ?? $rows->firstWhere('scope_key', 'all');
        if ($row === null || $row->account === null) {
            throw new FinanceException("No account is set for \"$role\"".($scope ? " ($scope)" : '').'. Set it in Finance > Account roles.');
        }

        return $row->account;
    }

    public function createCostCenter(int $organizationId, string $code, string $name, string $kind = 'cost', ?int $parentId = null, ?int $branchId = null, ?int $departmentId = null): CostCenter
    {
        if (CostCenter::query()->where(['organization_id' => $organizationId, 'code' => $code])->exists()) {
            throw ValidationException::withMessages(['code' => 'That cost centre code is already used.']);
        }

        return CostCenter::create(['organization_id' => $organizationId, 'code' => $code, 'name' => $name, 'kind' => in_array($kind, ['cost', 'profit'], true) ? $kind : 'cost', 'parent_id' => $parentId, 'branch_id' => $branchId, 'department_id' => $departmentId]);
    }
}
