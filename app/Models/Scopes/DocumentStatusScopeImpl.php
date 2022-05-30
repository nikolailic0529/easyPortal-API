<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @mixin Model
 */
trait DocumentStatusScopeImpl {
    public static function bootDocumentStatusScopeImpl(): void {
        static::addGlobalScope(app()->make(DocumentStatusScope::class));
    }
}
