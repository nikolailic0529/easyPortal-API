<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\DocumentEntry;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasDocumentEntries {
    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }
}
