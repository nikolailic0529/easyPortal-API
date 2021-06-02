<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Models\Casts\Statistics;
use App\Services\Logger\Models\Enums\Level;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Log.
 *
 * @property string                                                                         $id
 * @property \App\Services\Logger\Models\Enums\Level                                        $level
 * @property \App\Services\Logger\Models\Enums\Type                                         $type
 * @property string                                                                         $action
 * @property \App\Services\Logger\Models\Enums\Status|null                                  $status
 * @property string|null                                                                    $parent_id
 * @property int                                                                            $index
 * @property string|null                                                                    $object_type
 * @property string|null                                                                    $object_id
 * @property int                                                                            $duration
 * @property \Carbon\CarbonImmutable                                                        $created_at
 * @property \Carbon\CarbonImmutable                                                        $updated_at
 * @property \Carbon\CarbonImmutable|null                                                   $finished_at
 * @property \App\Services\Logger\Models\Casts\Statistics|null                              $statistics
 * @property array|null                                                                     $context
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Services\Logger\Models\Log> $children
 * @property \App\Services\Logger\Models\Log|null                                           $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\Log query()
 * @mixin \Eloquent
 */
class Log extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'logs';

    protected const CASTS = [
        'level'       => Level::class,
        'type'        => Type::class,
        'status'      => Status::class,
        'finished_at' => 'datetime',
        'statistics'  => Statistics::class,
        'context'     => 'json',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    public function parent(): BelongsTo {
        return $this->belongsTo(self::class);
    }

    public function setParentAttribute(?Log $parent): void {
        $this->parent()->associate($parent);
    }

    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }
}
