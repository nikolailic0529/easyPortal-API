<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Concerns;

use App\Services\DataLoader\Testing\Data\Data;

trait WithData {
    /**
     * @var class-string<Data>|null
     */
    private ?string $data = null;

    /**
     * @return class-string<Data>|null
     */
    public function getData(): ?string {
        return $this->data;
    }

    /**
     * @param class-string<Data>|null $data
     */
    public function setData(?string $data): static {
        $this->data = $data;

        return $this;
    }
}
