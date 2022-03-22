<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @see \App\Models\Scopes\DocumentType
 *
 * @mixin Model
 */
trait DocumentTypeScope {
    public static function bootDocumentTypeScope(): void {
        static::addGlobalScope(app()->make(DocumentType::class));
    }
}
