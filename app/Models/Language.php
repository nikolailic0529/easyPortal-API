<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Language.
 *
 * @property string                       $id
 * @property string                       $code
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\LanguageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Language extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'languages';

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatedPropertyKeys(string $property): array {
        return [
            "models.{$this->getMorphClass()}.{$property}.{$this->code}",
        ];
    }
}
