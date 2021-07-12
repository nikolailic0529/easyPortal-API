<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Coverage.
 *
 * @property string                                                           $id
 * @property string                                                           $key
 * @property string                                                           $name
 * @property \Carbon\CarbonImmutable                                          $created_at
 * @property \Carbon\CarbonImmutable                                          $updated_at
 * @property \Carbon\CarbonImmutable|null                                     $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset> $assets
 * @method static \Database\Factories\CoverageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coverage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coverage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coverage query()
 * @mixin \Eloquent
 */
class Coverage extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'coverages';

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

    public function assets(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Asset::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
