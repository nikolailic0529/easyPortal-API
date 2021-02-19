<?php declare(strict_types = 1);

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class SignupResource extends UserResource {
    /**
     * @inheritdoc
     */
    public function toResponse($request) {
        return (new JsonResponse())
            ->setStatusCode($this->resource->wasRecentlyCreated ? 201 : 200)
            ->setData(true);
    }
}
