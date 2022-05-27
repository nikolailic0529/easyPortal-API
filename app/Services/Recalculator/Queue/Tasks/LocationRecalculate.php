<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Models\Location;
use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculate<Location>
 */
class LocationRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-recalculator-location-recalculate';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(LocationsProcessor::class);
    }
}
