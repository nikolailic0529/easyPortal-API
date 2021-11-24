<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;

/**
 * App\Models\ResellerStatus
 *
 * @property string $id
 * @property string $reseller_id
 * @property string $status_id
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerStatus query()
 * @mixin \Eloquent
 */
class ResellerStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_statuses';
}
