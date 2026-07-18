<?php

namespace Crater\Exceptions;

use DomainException;

class FinalizedInvoiceMutationException extends DomainException
{
    public function __construct(string $message = 'Une facture finalisée ne peut plus être modifiée ou supprimée. Utilisez un avoir.')
    {
        parent::__construct($message);
    }
}
