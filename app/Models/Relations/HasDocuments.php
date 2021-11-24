<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasDocuments {
    public function documents(): HasMany {
        return $this->hasMany(Document::class);
    }
}
