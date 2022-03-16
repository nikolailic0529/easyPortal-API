<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasDocuments;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Language.
 *
 * @property string                         $id
 * @property string                         $code
 * @property string                         $name
 * @property \Carbon\CarbonImmutable        $created_at
 * @property \Carbon\CarbonImmutable        $updated_at
 * @property \Carbon\CarbonImmutable|null   $deleted_at
 * @property-read Collection<int, Document> $documents
 * @method static \Database\Factories\LanguageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language query()
 * @mixin \Eloquent
 */
class Language extends Model implements Translatable {
    use HasFactory;
    use HasDocuments;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'languages';

    protected function getTranslatableKey(): ?string {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
