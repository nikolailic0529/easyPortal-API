<?php declare(strict_types = 1);

namespace App\Services\Search\Migrations;

use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Service;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class IndexesRebuild extends RawDataMigration {
    protected function runRawUp(): void {
        $this->runRebuild();
    }

    protected function runRawDown(): void {
        $this->runRebuild();
    }

    protected function runRebuild(): void {
        $container = Container::getInstance();
        $service   = $container->make(Service::class);
        $models    = $this->getSearchableModels();

        foreach ($models as $model) {
            $job = $service->getSearchableModelJob($model);

            if ($job) {
                $container->make($job)->dispatch();
            }
        }
    }

    /**
     * @return array<class-string<Model&Searchable>>
     */
    protected function getSearchableModels(): array {
        $service = Container::getInstance()->make(Service::class);
        $models  = $service->getSearchableModels();

        return $models;
    }
}
