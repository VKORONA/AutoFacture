<?php

namespace Crater\Policies;

use Crater\Models\CreditNote;
use Crater\Models\Invoice;
use Crater\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class CreditNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return BouncerFacade::can('view-credit-note', CreditNote::class);
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->hasCompany($creditNote->company_id)
            && BouncerFacade::can('view-credit-note', CreditNote::class);
    }

    public function create(User $user, Invoice $invoice): bool
    {
        return $user->hasCompany($invoice->company_id)
            && BouncerFacade::can('create-credit-note', CreditNote::class);
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return false;
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return false;
    }
}
