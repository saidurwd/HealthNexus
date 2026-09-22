<?php

namespace App\Policies;

use App\Models\Billing\BillingInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.invoice.view');
    }

    public function view(User $user, BillingInvoice $invoice): bool
    {
        return $user->can('billing.invoice.view') && $this->inScope($user, $invoice);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.invoice.create');
    }

    public function update(User $user, BillingInvoice $invoice): bool
    {
        return $user->can('billing.invoice.update') && $this->inScope($user, $invoice);
    }

    public function finalize(User $user, BillingInvoice $invoice): bool
    {
        return $user->can('billing.invoice.finalize') && $this->inScope($user, $invoice);
    }

    public function cancel(User $user, BillingInvoice $invoice): bool
    {
        return $user->can('billing.invoice.cancel') && $this->inScope($user, $invoice);
    }

    public function writeOff(User $user, BillingInvoice $invoice): bool
    {
        return $user->can('billing.invoice.writeoff') && $this->inScope($user, $invoice);
    }

    private function inScope(User $user, BillingInvoice $invoice): bool
    {
        return $user->companies()->where('companies.id', $invoice->company_id)->exists()
            && $user->branches()->where('branches.id', $invoice->branch_id)->exists();
    }
}
