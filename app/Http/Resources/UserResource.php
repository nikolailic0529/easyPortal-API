<?php declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\User;
use LastDragon_ru\LaraASP\Spa\Http\Resources\Resource;

/**
 * @property \App\Models\User $resource
 */
class UserResource extends Resource {
    public function __construct(User $resource) {
        parent::__construct($resource);
    }

    /**
     * @inheritdoc
     */
    public function toArray($request): array {
        return [
            'given_name'  => $this->resource->given_name,
            'family_name' => $this->resource->family_name,
        ];
    }
}
