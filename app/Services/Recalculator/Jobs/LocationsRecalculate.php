<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends \App\Services\Recalculator\Jobs\Recalculate<\App\Models\Location>
 */
class LocationsRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-recalculator-locations-recalculate';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(LocationsProcessor::class);
    }
}
