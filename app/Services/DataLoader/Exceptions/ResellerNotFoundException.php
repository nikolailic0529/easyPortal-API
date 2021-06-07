<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Asset;

use function sprintf;

class ResellerNotFoundException extends InvalidData {
    public function __construct(
        protected string $id,
        protected Asset $asset,
    ) {
        parent::__construct(sprintf(
            'Reseller `%s` not found (asset `%s`).',
            $id,
            $asset->id,
        ));
    }
}
