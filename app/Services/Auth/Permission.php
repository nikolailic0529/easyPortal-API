<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Stringable;

abstract class Permission implements Stringable {
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    public function __toString(): string {
        return $this->getName();
    }
}
