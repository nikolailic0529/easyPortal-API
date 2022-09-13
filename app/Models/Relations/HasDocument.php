<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Document $document
 *
 * @mixin Model
 */
trait HasDocument {
    /**
     * @return BelongsTo<Document, self>
     */
    public function document(): BelongsTo {
        return $this->belongsTo(Document::class);
    }

    public function setDocumentAttribute(Document $document): void {
        $this->document()->associate($document);
    }
}
