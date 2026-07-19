<?php

namespace Crater\Policies;

use Crater\Models\Invoice;
use Crater\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return BouncerFacade::can('view-invoice', Invoice::class);
    }

    public function view(User $user, Invoice $invoice)
    {
        return BouncerFacade::can('view-invoice', $invoice)
            && $user->hasCompany($invoice->company_id);
    }

    public function create(User $user)
    {
        return BouncerFacade::can('create-invoice', Invoice::class);
    }

    public function update(User $user, Invoice $invoice)
    {
        if ($invoice->finalized_at) {
            return false;
        }

        return BouncerFacade::can('edit-invoice', $invoice)
            && $user->hasCompany($invoice->company_id)
            && $invoice->allow_edit;
    }

    public function delete(User $user, Invoice $invoice)
    {
        if ($invoice->finalized_at) {
            return false;
        }

        return BouncerFacade::can('delete-invoice', $invoice)
            && $user->hasCompany($invoice->company_id);
    }

    public function restore(User $user, Invoice $invoice)
    {
        return $this->delete($user, $invoice);
    }

    public function forceDelete(User $user, Invoice $invoice)
    {
        return false;
    }

    public function send(User $user, Invoice $invoice)
    {
        return BouncerFacade::can('send-invoice', $invoice)
            && $user->hasCompany($invoice->company_id);
    }

    public function deleteMultiple(User $user)
    {
        return BouncerFacade::can('delete-invoice', Invoice::class);
    }
}
