<?php

namespace Crater\Tenancy;

use Closure;
use LogicException;

class CompanyContext
{
    private ?int $companyId = null;

    public function set(int $companyId): void
    {
        if ($companyId < 1) {
            throw new LogicException('L’identifiant d’entreprise doit être strictement positif.');
        }

        $this->companyId = $companyId;
    }

    public function clear(): void
    {
        $this->companyId = null;
    }

    public function hasCompany(): bool
    {
        return $this->companyId !== null;
    }

    public function id(): int
    {
        if ($this->companyId === null) {
            throw new LogicException('Aucune entreprise active dans le contexte courant.');
        }

        return $this->companyId;
    }

    public function idOrNull(): ?int
    {
        return $this->companyId;
    }

    public function runWith(int $companyId, Closure $callback): mixed
    {
        $previousCompanyId = $this->companyId;
        $this->set($companyId);

        try {
            return $callback();
        } finally {
            $this->companyId = $previousCompanyId;
        }
    }
}
