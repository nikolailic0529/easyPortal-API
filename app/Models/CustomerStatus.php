<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * CustomerStatus.
 *
 * @property string               $id
 * @property string               $customer_id
 * @property string               $status_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static CustomerStatusFactory factory(...$parameters)
 * @method static Builder<CustomerStatus>|CustomerStatus newModelQuery()
 * @method static Builder<CustomerStatus>|CustomerStatus newQuery()
 * @method static Builder<CustomerStatus>|CustomerStatus query()
 */
class CustomerStatus extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_statuses';
}
