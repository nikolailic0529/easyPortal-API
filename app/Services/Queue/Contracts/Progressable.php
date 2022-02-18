<?php declare(strict_types = 1);

namespace App\Services\Queue\Contracts;

interface Progressable {
    /**
     * @return callable(mixed ...$args): \App\Services\Queue\Progress|null
     */
    public function getProgressCallback(): callable;

    /**
     * @return callable(mixed ...$args): bool
     */
    public function getResetProgressCallback(): callable;
}
