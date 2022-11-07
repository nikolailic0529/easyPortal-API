<?php declare(strict_types = 1);

namespace App\Utils\Validation\Traits;

use Illuminate\Contracts\Validation\DataAwareRule;

/**
 * @see DataAwareRule
 *
 * @mixin DataAwareRule
 */
trait WithData {
    /**
     * @var array<mixed>|null
     */
    private ?array $data = null;

    /**
     * @return array<mixed>|null
     */
    public function getData(): ?array {
        return $this->data;
    }

    /**
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function setData(mixed $data): static {
        $this->data = $data;

        return $this;
    }
}
