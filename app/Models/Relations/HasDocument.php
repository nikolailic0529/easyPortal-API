<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
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
