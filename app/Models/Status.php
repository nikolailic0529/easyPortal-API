<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Status.
 *
 * @property string                       $id
 * @property string                       $object_type
 * @property string                       $key
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\StatusFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Status extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'statuses';

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
