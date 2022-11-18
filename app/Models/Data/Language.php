<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Document;
use App\Models\Relations\HasDocuments;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Language.
 *
 * @property string                         $id
 * @property string                         $code
 * @property string                         $name
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property-read Collection<int, Document> $documents
 * @method static LanguageFactory factory(...$parameters)
 * @method static Builder<Language> newModelQuery()
 * @method static Builder<Language> newQuery()
 * @method static Builder<Language> query()
 */
class Language extends Model implements DataModel, Translatable {
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
