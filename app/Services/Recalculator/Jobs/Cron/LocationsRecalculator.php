<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends \App\Services\Recalculator\Jobs\Cron\Recalculator<\App\Models\Location>
 */
class LocationsRecalculator extends Recalculator {
    public function displayName(): string {
        return 'ep-recalculator-locations-recalculator';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(LocationsProcessor::class);
    }
}
