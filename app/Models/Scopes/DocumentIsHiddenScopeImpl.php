<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @mixin Model
 */
trait DocumentIsHiddenScopeImpl {
    public static function bootDocumentIsHiddenScopeImpl(): void {
        static::addGlobalScope(app()->make(DocumentIsHiddenScope::class));
    }
}
