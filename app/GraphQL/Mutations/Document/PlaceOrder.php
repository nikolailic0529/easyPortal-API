<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Document;
use App\Models\Data\Status;

class PlaceOrder {
    public function __construct(
    ) {
        // empty
    }

    /**
     * @return array{result: bool, assets: bool}
     */
    public function __invoke(Document $document): array {
        $document->statuses()->syncWithoutDetaching(Status::Document_ORDERED);
        return [
            'result' => true,
        ];
    }
}
