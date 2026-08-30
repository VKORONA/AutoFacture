<?php

namespace Crater\Domain\ElectronicInvoicing\Data;

final readonly class ProviderConnectionResult
{
    public function __construct(
        public bool $successful,
        public string $code,
        public string $message,
        public array $metadata = [],
    ) {}
}
