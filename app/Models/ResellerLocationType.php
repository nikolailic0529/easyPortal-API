<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerLocationTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ResellerLocationType.
 *
 * @property string               $id
 * @property string               $reseller_location_id
 * @property string               $type_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static ResellerLocationTypeFactory factory(...$parameters)
 * @method static Builder<ResellerLocationType>|ResellerLocationType newModelQuery()
 * @method static Builder<ResellerLocationType>|ResellerLocationType newQuery()
 * @method static Builder<ResellerLocationType>|ResellerLocationType query()
 */
class ResellerLocationType extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_location_types';
}
