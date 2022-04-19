<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;
use League\Geotools\Geohash\Geohash;

class MapInput extends JsonObject {
    public int $level;

    /**
     * @var array<Geohash>|null
     */
    #[JsonObjectArray(Geohash::class)]
    public ?array $boundaries = null;

    /**
     * @var array<mixed>|null
     */
    public ?array $locations = null;

    /**
     * @var array<mixed>|null
     */
    public ?array $assets = null;

    /**
     * Added by Lighthouse by `@field`.
     */
    protected mixed $directive = null;
}
