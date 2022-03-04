<?php declare(strict_types = 1);

namespace App\Services\Search\Migrations;

use App\Services\Search\Service;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class IndexesRebuild extends RawDataMigration {
    protected function runRawUp(): void {
        $container = $this->getContainer();
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
     * @return array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>>
     */
    protected function getSearchableModels(): array {
        $service = $this->getContainer()->make(Service::class);
        $models  = $service->getSearchableModels();

        return $models;
    }
}
