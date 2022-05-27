<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use App\Models\Reseller;
use App\Services\Recalculator\Processor\Processors\ResellersProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculator<Reseller>
 */
class ResellersRecalculator extends Recalculator {
    public function displayName(): string {
        return 'ep-recalculator-resellers-recalculator';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(ResellersProcessor::class);
    }
}
