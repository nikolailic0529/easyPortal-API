<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Service;
use App\Utils\Cache\CacheKeyable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

class Stop implements CacheKeyable {
    public function __construct(
        protected Service $service,
    ) {
        // empty
    }

    public function exists(Job $job, string $id): bool {
        return $this->service->has([$this, $job, $id]);
    }

    public function set(Job $job, string $id): ?DateTimeInterface {
        return $this->service->set([$this, $job, $id], Date::now());
    }
}
