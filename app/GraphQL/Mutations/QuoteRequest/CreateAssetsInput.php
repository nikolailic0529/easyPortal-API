<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Utils\JsonObject\JsonObject;

class CreateAssetsInput extends JsonObject {
    public string $asset_id;
    public string $duration_id;
    public string $service_level_id;
}
