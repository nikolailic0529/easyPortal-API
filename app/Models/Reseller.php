<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasLocations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Reseller.
 *
 * @property string                                                                 $id
 * @property string                                                                 $name
 * @property int                                                                    $customers_count
 * @property int                                                                    $locations_count
 * @property int                                                                    $assets_count
 * @property \Carbon\CarbonImmutable                                                $created_at
 * @property \Carbon\CarbonImmutable                                                $updated_at
 * @property \Carbon\CarbonImmutable|null                                           $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|array<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection|array<\App\Models\Location>   $locations
 * @method static \Database\Factories\ResellerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereAssetsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereCustomersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereLocationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Reseller extends Model {
    use HasFactory;
    use HasAssets;
    use HasLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'resellers';
}
