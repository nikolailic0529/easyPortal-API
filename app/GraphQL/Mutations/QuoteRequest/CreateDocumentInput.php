<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Utils\JsonObject\JsonObject;

class CreateDocumentInput extends JsonObject {
    public string $document_id;
    public string $duration_id;
}
