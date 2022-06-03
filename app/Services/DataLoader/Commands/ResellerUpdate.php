<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\ResellerLoader;
use App\Services\I18n\Formatter;

use function array_merge;

/**
 * @extends ObjectUpdate<ResellerLoader>
 */
class ResellerUpdate extends ObjectUpdate {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--a|assets : Load assets}',
            '{--A|no-assets : Skip assets (default)}',
            '{--d|documents : Load documents}',
            '{--D|no-documents : Skip documents (default)}',
            '{--assets-documents : Load assets documents to calculate extended warranty, required --assets (default)}',
            '{--no-assets-documents : Skip assets documents}',
        ]);
    }

    public function __invoke(Formatter $formatter, ResellerLoader $loader): int {
        $loader = $loader
            ->setWithAssets($this->getBoolOption('assets', false))
            ->setWithAssetsDocuments($this->getBoolOption('assets-documents', true))
            ->setWithDocuments($this->getBoolOption('documents', false));

        return $this->process($formatter, $loader);
    }
}
