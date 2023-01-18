<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @see DocumentIsDocumentScope
 *
 * @mixin Model
 */
trait DocumentIsDocumentScopeImpl {
    public static function bootDocumentIsDocumentScopeImpl(): void {
        static::addGlobalScope(app()->make(DocumentIsDocumentScope::class));
    }
}
