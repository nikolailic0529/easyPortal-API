<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Stringable;

abstract class Permission implements Stringable {
    protected function __construct(
        protected string $name,
        protected bool $orgAdmin = false,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    public function isOrgAdmin(): bool {
        return $this->orgAdmin;
    }

    public function __toString(): string {
        return $this->getName();
    }
}
