<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

trait WithContacts {
    protected bool $withContacts = true;

    public function isWithContacts(): bool {
        return $this->withContacts;
    }

    public function setWithContacts(bool $withContacts): static {
        $this->withContacts = $withContacts;

        return $this;
    }
}
