<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Language;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasLanguage {
    /**
     * @return BelongsTo<Language, self>
     */
    #[CascadeDelete(false)]
    public function language(): BelongsTo {
        return $this->belongsTo(Language::class);
    }

    public function setLanguageAttribute(?Language $language): void {
        $this->language()->associate($language);
    }
}
