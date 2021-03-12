<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasLocations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization.
 *
 * @property string                                                               $id
 * @property string|null                                                          $subdomain
 * @property string                                                               $name
 * @property int                                                                  $customers_count
 * @property int                                                                  $locations_count
 * @property int                                                                  $assets_count
 * @property \Carbon\CarbonImmutable                                              $created_at
 * @property \Carbon\CarbonImmutable                                              $updated_at
 * @property \Carbon\CarbonImmutable|null                                         $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection|array<\App\Models\Location> $locations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereAssetsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCustomersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereLocationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereSubdomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Organization extends Model {
    use HasFactory;
    use HasAssets;
    use HasLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organizations';
}
