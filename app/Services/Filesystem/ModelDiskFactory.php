<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Models\Model;
use App\Services\Filesystem\Disks\ModelDisk;
use Illuminate\Contracts\Container\Container;
use LogicException;

class ModelDiskFactory {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public function getDisk(Model $model): ModelDisk {
        if (!$model->exists) {
            throw new LogicException('Model is not exists.');
        }

        return $this->container->make(ModelDisk::class, [
            'model' => $model,
        ]);
    }
}
