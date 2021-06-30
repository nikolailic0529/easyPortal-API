<?php declare(strict_types = 1);

namespace App\Services\Queue;

interface Progressable {
    /**
     * @return callable(): \App\Services\Queue\Progress|null
     */
    public function getProgressProvider(): callable;
}
