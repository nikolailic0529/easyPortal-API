<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;

class FailedToProcessViewAssetDocumentNoDocument extends FailedToProcessObject {
    public function __construct(
        protected Asset $asset,
        protected ViewAssetDocument $document,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process ViewAssetDocument for Asset `%s`: `document` is null.',
            $this->asset->getKey(),
        ), $previous);

        $this->setLevel(LogLevel::NOTICE);
        $this->setContext([
            'documentNumber' => $this->document->documentNumber,
        ]);
    }
}
