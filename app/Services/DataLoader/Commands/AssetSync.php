<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loaders\AssetLoader;

use function array_merge;

/**
 * @extends ObjectSync<AssetLoader>
 */
class AssetSync extends ObjectSync {
    /**
     * @inheritDoc
     */
    protected static function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--warranty-check : run warranty check before update}',
            '{--no-warranty-check : do not run warranty check before update (default)}',
        ]);
    }

    public function __invoke(AssetLoader $loader): int {
        $loader = $loader
            ->setWithWarrantyCheck($this->getBoolOption('warranty-check', false));

        return $this->process($loader);
    }
}
