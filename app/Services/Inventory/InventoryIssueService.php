<?php

namespace App\Services\Inventory;

use App\Models\Department;
use App\Models\Inventory\InventoryIssue;
use App\Models\Inventory\InventoryIssueLine;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequest;
use App\Models\Inventory\InventoryStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** A department asks a store for supplies and the storekeeper issues them oldest-expiry first; unused items can come back (S08). */
class InventoryIssueService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly InventoryStockService $stock) {}

    /** @param  list<array{item_id: int, quantity: float|int|string}>  $lines  base units */
    public function request(User $by, Department $department, InventoryStore $store, array $lines, ?string $notes = null): InventoryRequest
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($by, $department, $store, $lines, $notes) {
            $request = InventoryRequest::create(['organization_id' => $store->organization_id, 'department_id' => $department->id, 'store_id' => $store->id, 'number' => $this->numbers->next('request', $store->organization_id, $by), 'notes' => $notes, 'requested_by' => $by->id]);
            foreach ($lines as $line) {
                InventoryItem::query()->where('organization_id', $store->organization_id)->findOrFail($line['item_id']);
                if (bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Quantities must be above zero.']);
                }
                $request->lines()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);
            }

            return $request->refresh();
        });
    }

    /**
     * Issues what is asked for, or less if the store cannot cover it (`partially_issued`).
     *
     * @param  array<int, float|int|string>|null  $quantities  request line id => quantity to issue; null = the outstanding quantity of every line
     */
    public function issueRequest(InventoryRequest $request, User $by, ?array $quantities = null): InventoryIssue
    {
        return DB::transaction(function () use ($request, $by, $quantities) {
            $request = InventoryRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($request->status, ['submitted', 'partially_issued'], true)) {
                throw ValidationException::withMessages(['status' => "A {$request->status} request cannot be issued."]);
            }
            $store = $request->store;
            $issue = InventoryIssue::create(['organization_id' => $request->organization_id, 'store_id' => $store->id, 'department_id' => $request->department_id, 'request_id' => $request->id, 'number' => $this->numbers->next('issue', $request->organization_id, $by), 'issued_by' => $by->id, 'issued_at' => now()]);
            $issued = 0;
            foreach ($request->lines()->get() as $line) {
                $item = InventoryItem::findOrFail($line->item_id);
                $outstanding = bcsub((string) $line->quantity, (string) $line->issued_quantity, 4);
                $want = (string) ($quantities[$line->id] ?? $outstanding);
                if (bccomp($want, $outstanding, 4) > 0) {
                    throw ValidationException::withMessages(['quantity' => "{$item->name}: only {$outstanding} is still asked for."]);
                }
                $give = $this->cap($store, $item, $want);
                if (bccomp($give, '0', 4) <= 0) {
                    continue;
                }
                $this->giveOut($issue, $store, $item, $give, $by);
                $line->update(['issued_quantity' => bcadd((string) $line->issued_quantity, $give, 4)]);
                $issued++;
            }
            if ($issued === 0) {
                throw ValidationException::withMessages(['quantity' => 'The store has none of the requested items in date.']);
            }
            $done = $request->lines()->get()->every(fn ($l) => bccomp((string) $l->issued_quantity, (string) $l->quantity, 4) >= 0);
            $request->update(['status' => $done ? 'issued' : 'partially_issued']);

            return $issue->refresh();
        });
    }

    public function reject(InventoryRequest $request, string $reason): InventoryRequest
    {
        if (! in_array($request->status, ['submitted', 'partially_issued'], true)) {
            throw ValidationException::withMessages(['status' => "A {$request->status} request cannot be rejected."]);
        }
        $request->update(['status' => 'rejected', 'notes' => Str::limit(trim(($request->notes ? $request->notes.' · ' : '').'Rejected: '.$reason), 300, '')]);

        return $request->refresh();
    }

    /** Issue without a request (the storekeeper hands something over). @param  list<array{item_id: int, quantity: float|int|string}>  $lines */
    public function issueDirect(User $by, InventoryStore $store, Department $department, array $lines): InventoryIssue
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($by, $store, $department, $lines) {
            $issue = InventoryIssue::create(['organization_id' => $store->organization_id, 'store_id' => $store->id, 'department_id' => $department->id, 'number' => $this->numbers->next('issue', $store->organization_id, $by), 'issued_by' => $by->id, 'issued_at' => now()]);
            foreach ($lines as $line) {
                $this->giveOut($issue, $store, InventoryItem::query()->where('organization_id', $store->organization_id)->findOrFail($line['item_id']), (string) $line['quantity'], $by);
            }

            return $issue->refresh();
        });
    }

    /** Unused stock back to the store. */
    public function returnLine(InventoryIssueLine $line, User $by, float|int|string $quantity, string $reason): InventoryIssueLine
    {
        return DB::transaction(function () use ($line, $by, $quantity, $reason) {
            $line = InventoryIssueLine::query()->lockForUpdate()->findOrFail($line->id);
            $issue = InventoryIssue::findOrFail($line->issue_id);
            $room = bcsub((string) $line->quantity, (string) $line->returned_quantity, 4);
            if (bccomp((string) $quantity, '0', 4) <= 0 || bccomp((string) $quantity, $room, 4) > 0) {
                throw ValidationException::withMessages(['quantity' => "You can return at most {$room}."]);
            }
            $returnNo = bcadd((string) $line->returned_quantity, (string) $quantity, 4);
            $this->stock->receive($issue->store, InventoryItem::findOrFail($line->item_id), (int) $line->batch_id, (string) $quantity, 'return', "ISSRET:{$line->id}:{$returnNo}", (string) $line->unit_cost, InventoryIssue::class, $issue->id, $by, $reason);
            $line->update(['returned_quantity' => $returnNo]);

            return $line->refresh();
        });
    }

    private function giveOut(InventoryIssue $issue, InventoryStore $store, InventoryItem $item, string $quantity, User $by): void
    {
        foreach ($this->stock->allocate($store, $item, $quantity) as $pick) {
            $line = $issue->lines()->create(['item_id' => $item->id, 'batch_id' => $pick['batch_id'], 'quantity' => $pick['quantity'], 'unit_cost' => $pick['unit_cost']]);
            $this->stock->take($store, $item, $pick['batch_id'], $pick['quantity'], 'issue', "ISS:{$issue->id}:{$line->id}", $pick['unit_cost'], InventoryIssue::class, $issue->id, $by);
        }
    }

    /** What the store can really give of a wanted quantity. */
    private function cap(InventoryStore $store, InventoryItem $item, string $want): string
    {
        $usable = $this->stock->usable($store, $item);

        return $store->allow_negative || bccomp($usable, $want, 4) >= 0 ? $want : $usable;
    }
}
