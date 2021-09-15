<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Stringable;

class Permission implements Stringable {
    public function __construct(
        protected string $name,
        protected bool $orgAdmin,
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
