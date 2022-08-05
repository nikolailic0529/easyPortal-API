<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tag.
 *
 * @property string                      $id
 * @property string                      $name
 * @property CarbonImmutable             $created_at
 * @property CarbonImmutable             $updated_at
 * @property CarbonImmutable|null        $deleted_at
 * @property-read Collection<int, Asset> $assets
 * @method static TagFactory factory(...$parameters)
 * @method static Builder|Tag newModelQuery()
 * @method static Builder|Tag newQuery()
 * @method static Builder|Tag query()
 */
class Tag extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'tags';

    #[CascadeDelete(true)]
    public function assets(): BelongsToMany {
        $pivot = new AssetTag();

        return $this
            ->belongsToMany(Asset::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
