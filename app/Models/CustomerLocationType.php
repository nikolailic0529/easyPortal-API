<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * CustomerLocationType.
 *
 * @property string               $id
 * @property string               $customer_location_id
 * @property string               $type_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|CustomerLocationType newModelQuery()
 * @method static Builder|CustomerLocationType newQuery()
 * @method static Builder|CustomerLocationType query()
 * @mixin Eloquent
 */
class CustomerLocationType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_location_types';
}
