<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerLocationTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * CustomerLocationType.
 *
 * @property string               $id
 * @property string               $customer_location_id
 * @property string               $type_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static CustomerLocationTypeFactory factory(...$parameters)
 * @method static Builder<CustomerLocationType>|CustomerLocationType newModelQuery()
 * @method static Builder<CustomerLocationType>|CustomerLocationType newQuery()
 * @method static Builder<CustomerLocationType>|CustomerLocationType query()
 */
class CustomerLocationType extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_location_types';
}
