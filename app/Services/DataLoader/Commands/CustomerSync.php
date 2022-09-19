<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Services\I18n\Formatter;

use function array_merge;

/**
 * @extends ObjectSync<CustomerLoader>
 */
class CustomerSync extends ObjectSync {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--from= : Start processing from given DateTime/DateInterval}',
            '{--warranty-check : Run warranty check before update}',
            '{--no-warranty-check : Do not run warranty check before update (default)}',
            '{--a|assets : Load assets}',
            '{--A|no-assets : Skip assets (default)}',
            '{--d|documents : Load documents}',
            '{--D|no-documents : Skip documents (default)}',
        ]);
    }

    public function __invoke(Formatter $formatter, CustomerLoader $loader): int {
        $loader = $loader
            ->setFrom($this->getDateTimeOption('from'))
            ->setWithWarrantyCheck($this->getBoolOption('warranty-check', false))
            ->setWithAssets($this->getBoolOption('assets', false))
            ->setWithDocuments($this->getBoolOption('documents', false));

        return $this->process($formatter, $loader);
    }
}
