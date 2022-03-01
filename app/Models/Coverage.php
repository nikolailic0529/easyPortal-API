<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
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

    protected function getTranslatableKey(): ?string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    #[CascadeDelete(true)]
    public function assets(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Asset::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
