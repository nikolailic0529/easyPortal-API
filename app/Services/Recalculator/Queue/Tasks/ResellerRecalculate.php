<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Models\Reseller;
use App\Services\Recalculator\Processor\Processors\ResellersProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculate<Reseller>
 */
class ResellerRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-recalculator-reseller-recalculate';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(ResellersProcessor::class);
    }
}
