<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Models\Casts\Statistics;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use Carbon\CarbonImmutable;
use Database\Factories\Logs\LogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Log.
 *
 * @property string                    $id
 * @property Category                  $category
 * @property string                    $action
 * @property Status|null               $status
 * @property string|null               $parent_id
 * @property int                       $index
 * @property string|null               $object_type
 * @property string|null               $object_id
 * @property float|null                $duration
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property CarbonImmutable|null      $finished_at
 * @property Statistics|null           $statistics
 * @property array|null                $context
 * @property-read Collection<int, Log> $children
 * @property Log|null                  $parent
 * @method static LogFactory factory(...$parameters)
 * @method static Builder|Log newModelQuery()
 * @method static Builder|Log newQuery()
 * @method static Builder|Log query()
 */
class Log extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'logs';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'category'    => Category::class,
        'status'      => Status::class,
        'finished_at' => 'datetime',
        'statistics'  => Statistics::class,
        'context'     => 'json',
    ];

    /**
     * @return BelongsTo<self, self>
     */
    public function parent(): BelongsTo {
        return $this->belongsTo(self::class);
    }

    public function setParentAttribute(?Log $parent): void {
        $this->parent()->associate($parent);
    }

    /**
     * @return HasMany<self>
     */
    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @deprecated Should be removed after moving model into \App\Models\Logs
     */
    protected static function newFactory(): Factory {
        return new LogFactory();
    }
}
