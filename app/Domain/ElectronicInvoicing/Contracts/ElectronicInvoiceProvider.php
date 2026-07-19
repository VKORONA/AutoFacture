<?php

namespace Crater\Domain\ElectronicInvoicing\Contracts;

use Crater\Domain\ElectronicInvoicing\Data\ProviderConnectionResult;
use Crater\Models\ElectronicInvoiceConnection;

interface ElectronicInvoiceProvider
{
    public function code(): string;

    public function testConnection(ElectronicInvoiceConnection $connection): ProviderConnectionResult;
}
