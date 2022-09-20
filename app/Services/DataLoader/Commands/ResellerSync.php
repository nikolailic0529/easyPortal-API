<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loaders\ResellerLoader;
use App\Services\I18n\Formatter;

use function array_merge;

/**
 * @extends ObjectSync<ResellerLoader>
 */
class ResellerSync extends ObjectSync {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--from= : Start processing from given DateTime/DateInterval}',
            '{--a|assets : Load assets}',
            '{--A|no-assets : Skip assets (default)}',
            '{--d|documents : Load documents}',
            '{--D|no-documents : Skip documents (default)}',
        ]);
    }

    public function __invoke(Formatter $formatter, ResellerLoader $loader): int {
        $loader = $loader
            ->setFrom($this->getDateTimeOption('from'))
            ->setWithAssets($this->getBoolOption('assets', false))
            ->setWithDocuments($this->getBoolOption('documents', false));

        return $this->process($formatter, $loader);
    }
}
