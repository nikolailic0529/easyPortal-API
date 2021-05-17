<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AssetCoverage.
 *
 * @property string                       $id
 * @property string                       $key
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\AssetCoverageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetCoverage extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_coverages';

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
            "models.{$this->getMorphClass()}.{$property}.{$this->key}",
        ];
    }
}
