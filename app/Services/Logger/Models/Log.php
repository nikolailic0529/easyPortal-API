<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Log.
 *
 * @property string                                                                              $id
 * @property \App\Services\Logger\Models\Enums\Type                                              $type
 * @property string                                                                              $action
 * @property \App\Services\Logger\Models\Enums\Status                                            $status
 * @property string|null                                                                         $guard
 * @property string|null                                                                         $auth_id
 * @property string|null                                                                         $parent_id
 * @property int|null                                                                            $index
 * @property int|null                                                                            $duration
 * @property int                                                                                 $entries_count
 * @property int                                                                                 $entries_emergency
 * @property int                                                                                 $entries_alert
 * @property int                                                                                 $entries_critical
 * @property int                                                                                 $entries_error
 * @property int                                                                                 $entries_warning
 * @property int                                                                                 $entries_notice
 * @property int                                                                                 $entries_info
 * @property int                                                                                 $entries_debug
 * @property int                                                                                 $models_created
 * @property int                                                                                 $models_updated
 * @property int                                                                                 $models_restored
 * @property int                                                                                 $models_deleted
 * @property int                                                                                 $models_force_deleted
 * @property int                                                                                 $jobs_dispatched
 * @property \Carbon\CarbonImmutable                                                             $created_at
 * @property \Carbon\CarbonImmutable                                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                                        $finished_at
 * @property array|null                                                                          $context
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Services\Logger\Models\Log>      $children
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Services\Logger\Models\LogEntry> $entries
 * @property \App\Services\Logger\Models\Log|null                                                $parent
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
        'type'        => Type::class,
        'status'      => Status::class,
        'finished_at' => 'datetime',
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

    public function entries(): HasMany {
        return $this->hasMany(LogEntry::class);
    }
}
