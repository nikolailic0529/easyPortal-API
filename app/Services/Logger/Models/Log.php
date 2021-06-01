<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Log.
 *
 * @property string                                                                              $id
 * @property \App\Services\Logger\Models\Enums\Type                                              $type
 * @property string                                                                              $action
 * @property \App\Services\Logger\Models\Enums\Status                                            $status
 * @property string|null                                                                         $parent_id
 * @property string|null                                                                         $user_id
 * @property string|null                                                                         $user_ip
 * @property int|null                                                                            $duration
 * @property int                                                                                 $entries_count
 * @property int                                                                                 $emergency_count
 * @property int                                                                                 $alert_count
 * @property int                                                                                 $critical_count
 * @property int                                                                                 $error_count
 * @property int                                                                                 $warning_count
 * @property int                                                                                 $notice_count
 * @property int                                                                                 $info_count
 * @property int                                                                                 $debug_count
 * @property int                                                                                 $models_created
 * @property int                                                                                 $models_updated
 * @property int                                                                                 $models_restored
 * @property int                                                                                 $models_deleted
 * @property int                                                                                 $models_force_deleted
 * @property \Carbon\CarbonImmutable                                                             $created_at
 * @property \Carbon\CarbonImmutable                                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                                        $finished_at
 * @property array|null                                                                          $context
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Services\Logger\Models\LogEntry> $entries
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

    public function entries(): HasMany {
        return $this->hasMany(LogEntry::class);
    }
}
