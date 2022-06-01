<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use App\Models\Asset;
use App\Services\Recalculator\Processor\Processors\AssetsProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculator<Asset>
 */
class AssetsRecalculator extends Recalculator {
    public function displayName(): string {
        return 'ep-recalculator-assets-recalculator';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(AssetsProcessor::class);
    }
}
