<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasDocument {
    #[CascadeDelete(false)]
    public function document(): BelongsTo {
        return $this->belongsTo(Document::class);
    }

    public function setDocumentAttribute(Document $document): void {
        $this->document()->associate($document);
    }
}
