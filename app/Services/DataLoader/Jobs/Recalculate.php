<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Callbacks\GetKey;
use App\Services\Queue\Job;
use App\Services\Queue\Queues;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

abstract class Recalculate extends Job implements Initializable {
    /**
     * @var array<string>
     */
    protected array $keys;

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::DATA_LOADER_RECALCULATE,
            ] + parent::getQueueConfig();
    }

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param array<\App\Models\Model>|\Illuminate\Support\Collection<\App\Models\Model> $models
     */
    public function setModels(Collection|array $models): static {
        $this->keys = (new Collection($models))->map(new GetKey())->values()->all();

        $this->initialized();

        return $this;
    }
}
