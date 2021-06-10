<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\ViewAssetDocument;

class ViewAssetDocumentNoDocument extends InvalidData {
    public function __construct(
        protected ViewAssetDocument|null $object = null,
    ) {
        parent::__construct('ViewAssetDocument doesn\'t contain Document.');
    }
}
