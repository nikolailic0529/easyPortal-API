<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Asset;
use App\Services\DataLoader\Schema\Types\CoverageEntry;
use Throwable;

use function sprintf;

class FailedToProcessViewAssetCoverageEntry extends FailedToProcessObject {
    public function __construct(
        protected Asset $asset,
        protected CoverageEntry $entry,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process CoverageEntry for Asset `%s`.',
            $this->asset->getKey(),
        ), $previous);

        $this->setContext([
            'entry' => $this->entry,
        ]);
    }
}
