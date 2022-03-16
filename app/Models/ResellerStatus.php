<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\ResellerStatus
 *
 * @property string               $id
 * @property string               $reseller_id
 * @property string               $status_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|ResellerStatus newModelQuery()
 * @method static Builder|ResellerStatus newQuery()
 * @method static Builder|ResellerStatus query()
 * @mixin Eloquent
 */
class ResellerStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_statuses';
}
