<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @see \App\Models\Scopes\DocumentTypeScope
 *
 * @mixin Model
 */
trait DocumentTypeScopeImpl {
    public static function bootDocumentTypeScopeImpl(): void {
        static::addGlobalScope(app()->make(DocumentTypeScope::class));
    }
}
