<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Database\Factories\LocationResellerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * LocationReseller.
 *
 * @property string               $id
 * @property string               $location_id
 * @property string               $reseller_id
 * @property int                  $assets_count
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static LocationResellerFactory factory(...$parameters)
 * @method static Builder<LocationReseller>|LocationReseller newModelQuery()
 * @method static Builder<LocationReseller>|LocationReseller newQuery()
 * @method static Builder<LocationReseller>|LocationReseller query()
 */
class LocationReseller extends Pivot implements Upsertable {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'location_resellers';

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['location_id', 'reseller_id'];
    }
}
