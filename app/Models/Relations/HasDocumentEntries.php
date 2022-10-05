<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\DocumentEntry;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Collection<int, DocumentEntry> $documentEntries
 *
 * @mixin Model
 */
trait HasDocumentEntries {
    /**
     * @return HasMany<DocumentEntry>
     */
    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }
}
