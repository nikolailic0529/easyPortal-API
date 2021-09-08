<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use Illuminate\Support\Collection;
use Throwable;

use function count;
use function sprintf;

class FailedToProcessViewAssetDocumentNoDocument extends FailedToProcessObject {
    /**
     * @param \Illuminate\Support\Collection<\App\Services\DataLoader\Schema\ViewAssetDocument> $entries
     */
    public function __construct(
        protected Asset $asset,
        protected ViewAssetDocument $document,
        protected Collection $entries,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process ViewAssetDocument for Asset `%s`: `document` is null.',
            $this->asset->getKey(),
        ), $previous);

        $this->setContext([
            'document' => $this->document,
            'entries'  => count($this->entries),
        ]);
    }
}
