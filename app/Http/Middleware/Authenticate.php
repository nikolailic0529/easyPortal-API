<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware {
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @inheritdoc
     */
    protected function redirectTo($request): ?string {
        return null;
    }
}
