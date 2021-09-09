<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\ViewAsset;
use Throwable;

use function sprintf;

class FailedToProcessViewAsset extends FailedToProcessObject {
    public function __construct(
        protected ViewAsset $asset,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Failed to process ViewAsset `%s`.', $this->asset->id), $previous);

        $this->setContext([
            'asset' => $this->asset,
        ]);
    }
}
