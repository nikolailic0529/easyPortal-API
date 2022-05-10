<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * ResellerLocationType.
 *
 * @property string               $id
 * @property string               $reseller_location_id
 * @property string               $type_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|ResellerLocationType newModelQuery()
 * @method static Builder|ResellerLocationType newQuery()
 * @method static Builder|ResellerLocationType query()
 */
class ResellerLocationType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_location_types';
}
