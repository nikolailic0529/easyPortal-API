<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\ResellerStatus
 *
 * @property string               $id
 * @property string               $reseller_id
 * @property string               $status_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ResellerStatusFactory factory(...$parameters)
 * @method static Builder<ResellerStatus>|ResellerStatus newModelQuery()
 * @method static Builder<ResellerStatus>|ResellerStatus newQuery()
 * @method static Builder<ResellerStatus>|ResellerStatus query()
 */
class ResellerStatus extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_statuses';
}
