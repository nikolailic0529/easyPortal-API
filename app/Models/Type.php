<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Type.
 *
 * @property string                       $id
 * @property string                       $object_type
 * @property string                       $key
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\TypeFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Type extends PolymorphicModel implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'types';

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    protected function getTranslatedPropertyKey(string $property): string {
        return "models.{$this->getMorphClass()}.{$property}.{$this->object_type}.{$this->key}";
    }
}
