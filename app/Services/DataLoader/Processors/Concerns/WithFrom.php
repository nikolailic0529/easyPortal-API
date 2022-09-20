<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Concerns;

use DateTimeInterface;

trait WithFrom {
    private ?DateTimeInterface $from = null;

    public function getFrom(): ?DateTimeInterface {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): static {
        $this->from = $from;

        return $this;
    }
}
