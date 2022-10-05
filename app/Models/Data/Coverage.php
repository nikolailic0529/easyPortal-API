<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\CoverageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Coverage.
 *
 * @property string                      $id
 * @property string                      $key
 * @property string                      $name
 * @property CarbonImmutable             $created_at
 * @property CarbonImmutable             $updated_at
 * @property CarbonImmutable|null        $deleted_at
 * @property-read Collection<int, Asset> $assets
 * @method static CoverageFactory factory(...$parameters)
 * @method static Builder|Coverage newModelQuery()
 * @method static Builder|Coverage newQuery()
 * @method static Builder|Coverage query()
 */
class Coverage extends Model implements DataModel, Translatable {
    use HasFactory;
    use TranslateProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'coverages';

    protected function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    /**
     * @return BelongsToMany<Asset>
     */
    public function assets(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Asset::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
