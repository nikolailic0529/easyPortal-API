<?php declare(strict_types = 1);

namespace App\Utils\Processor\Contracts;

use App\Utils\Processor\State;

interface StateStore {
    /**
     * @return array<string, mixed>|null
     */
    public function get(): ?array;

    public function save(State $state): State;

    public function delete(): bool;
}
