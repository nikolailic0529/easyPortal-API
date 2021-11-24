<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasDocument {
    public function document(): BelongsTo {
        return $this->belongsTo(Document::class);
    }

    public function setDocumentAttribute(Document $document): void {
        $this->document()->associate($document);
    }
}
