<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Services\I18n\Formatter;

use function array_merge;

/**
 * @extends ObjectUpdate<AssetLoader>
 */
class AssetUpdate extends ObjectUpdate {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--warranty-check : run warranty check before update}',
            '{--no-warranty-check : do not run warranty check before update (default)}',
            '{--d|documents : Load asset documents (and warranties) (default)}',
            '{--D|no-documents : Skip asset documents}',
        ]);
    }

    public function __invoke(Formatter $formatter, AssetLoader $loader): int {
        $loader = $loader
            // fixme(DataLoader)!: ->setWithWarrantyCheck((bool) $this->getBoolOption('warranty-check', false))
            ->setWithDocuments((bool) $this->getBoolOption('documents', true));

        return $this->process($formatter, $loader);
    }
}
