<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Services\I18n\Formatter;

use function array_merge;

/**
 * @extends ObjectUpdate<CustomerLoader>
 */
class CustomerUpdate extends ObjectUpdate {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--warranty-check : run warranty check before update}',
            '{--no-warranty-check : do not run warranty check before update (default)}',
            '{--a|assets : Load assets}',
            '{--A|no-assets : Skip assets (default)}',
            '{--d|documents : Load documents}',
            '{--D|no-documents : Skip documents (default)}',
            '{--assets-documents : Load assets documents to calculate extended warranty, required --assets (default)}',
            '{--no-assets-documents : Skip assets documents}',
        ]);
    }

    public function __invoke(Formatter $formatter, CustomerLoader $loader): int {
        $loader = $loader
            // fixme(DataLoader)!: ->setWithWarrantyCheck((bool) $this->getBoolOption('warranty-check', false))
            ->setWithAssets((bool) $this->getBoolOption('assets', false))
            ->setWithAssetsDocuments((bool) $this->getBoolOption('assets-documents', true))
            ->setWithDocuments((bool) $this->getBoolOption('documents', false));

        return $this->process($formatter, $loader);
    }
}
