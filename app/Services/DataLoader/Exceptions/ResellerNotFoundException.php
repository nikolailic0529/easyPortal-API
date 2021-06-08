<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;

use function sprintf;

class ResellerNotFoundException extends InvalidData {
    public function __construct(
        protected string $id,
        protected Asset|Document|AssetDocument $object,
    ) {
        parent::__construct(sprintf(
            'Reseller `%s` not found.',
            $id,
        ));
    }
}
