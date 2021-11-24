<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use function app;

/**
 * @see \App\Models\Scopes\DocumentType
 *
 * @mixin \App\Utils\Eloquent\Model
 */
trait DocumentTypeScope {
    public static function bootDocumentTypeScope(): void {
        static::addGlobalScope(app()->make(DocumentType::class));
    }
}
