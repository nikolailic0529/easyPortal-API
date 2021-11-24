<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Language;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasLanguage {
    public function language(): BelongsTo {
        return $this->belongsTo(Language::class);
    }

    public function setLanguageAttribute(?Language $language): void {
        $this->language()->associate($language);
    }
}
