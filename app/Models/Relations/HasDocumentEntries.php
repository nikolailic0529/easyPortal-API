<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\DocumentEntry;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
trait HasDocumentEntries {
    #[CascadeDelete(false)]
    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }
}
