<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Events;

class ModelsRecalculated {
    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string>                                     $keys
     */
    public function __construct(
        private string $model,
        private array $keys,
    ) {
        // empty
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        return $this->keys;
    }
}
