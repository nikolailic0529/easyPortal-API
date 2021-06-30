<?php declare(strict_types = 1);

namespace App\Services\Queue;

interface Progressable {
    public function getProgress(): ?Progress;
}
