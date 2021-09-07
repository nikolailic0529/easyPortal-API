<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use Throwable;

use function sprintf;

class FailedToCreateAssetInitialWarranty extends FailedToProcessObject {
    public function __construct(
        protected Asset $asset,
        protected ViewAssetDocument $document,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to create Initial Warranty for Asset `%s`.',
            $this->asset->getKey(),
        ), $previous);

        $this->setContext([
            'document' => $this->document,
        ]);
    }
}
