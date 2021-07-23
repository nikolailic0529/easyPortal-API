<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\DocumentEntry;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasDocumentEntries {
    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }
}
