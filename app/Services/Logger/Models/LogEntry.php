<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Models\Enums\Level;

/**
 * Log Entry.
 *
 * @property string                                  $id
 * @property string                                  $log_id
 * @property \App\Services\Logger\Models\Enums\Level $level
 * @property string                                  $event
 * @property string|null                             $object_type
 * @property string|null                             $object_id
 * @property \Carbon\CarbonImmutable                 $created_at
 * @property \Carbon\CarbonImmutable                 $updated_at
 * @property array|null                              $context
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\LogEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\LogEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Logger\Models\LogEntry query()
 * @mixin \Eloquent
 */
class LogEntry extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'log_entries';

    protected const CASTS = [
        'level'   => Level::class,
        'context' => 'json',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;
}
