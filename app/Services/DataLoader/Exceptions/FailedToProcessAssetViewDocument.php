<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\Types\ViewDocument;
use Throwable;

use function sprintf;

class FailedToProcessAssetViewDocument extends FailedToProcessObject {
    public function __construct(
        protected Asset $asset,
        protected ViewDocument $document,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process ViewDocument for Asset `%s`.',
            $this->asset->getKey(),
        ), $previous);

        $this->setContext([
            'document' => $this->document,
        ]);
    }
}
