<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use Throwable;

use function sprintf;

class FailedToCreateAssetWarranty extends FailedToProcessObject {
    public function __construct(
        protected Asset $asset,
        protected ViewAssetDocument $document,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to create Warranty for Asset `%s`.',
            $this->asset->getKey(),
        ), $previous);

        $this->setContext([
            'document' => $this->document,
        ]);
    }
}
