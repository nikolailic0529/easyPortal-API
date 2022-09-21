<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Language;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasLanguage {
    /**
     * @return BelongsTo<Language, self>
     */
    public function language(): BelongsTo {
        return $this->belongsTo(Language::class);
    }

    public function setLanguageAttribute(?Language $language): void {
        $this->language()->associate($language);
    }
}
