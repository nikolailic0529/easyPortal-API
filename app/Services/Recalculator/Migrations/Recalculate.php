<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Migrations;

use App\Services\Recalculator\Service;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class Recalculate extends RawDataMigration {
    protected function runRawUp(): void {
        $this->runRecalculate();
    }

    protected function runRawDown(): void {
        $this->runRecalculate();
    }

    protected function runRecalculate(): void {
        $container = Container::getInstance();
        $service   = $container->make(Service::class);
        $models    = $this->getModels();

        foreach ($models as $model) {
            $job = $service->getRecalculableModelJob($model);

            if ($job) {
                $container->make($job)->dispatch();
            }
        }
    }

    /**
     * @return array<class-string<Model>>
     */
    protected function getModels(): array {
        $service = Container::getInstance()->make(Service::class);
        $models  = $service->getRecalculableModels();

        return $models;
    }
}
