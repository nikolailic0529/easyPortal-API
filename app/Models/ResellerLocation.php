<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasLocation;
use App\Models\Relations\HasReseller;
use App\Models\Relations\HasTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Reseller Location.
 *
 * @property string                                                     $id
 * @property string                                                     $reseller_id
 * @property string                                                     $location_id
 * @property int                                                        $customers_count
 * @property int                                                        $assets_count
 * @property \Carbon\CarbonImmutable                                    $created_at
 * @property \Carbon\CarbonImmutable                                    $updated_at
 * @property \Carbon\CarbonImmutable|null                               $deleted_at
 * @property \App\Models\Location                                       $location
 * @property \App\Models\Reseller                                       $reseller
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type> $types
 * @method static \Database\Factories\ResellerLocationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocation query()
 * @mixin \Eloquent
 */
class ResellerLocation extends Model {
    use HasFactory;
    use HasTypes;
    use HasLocation;
    use HasReseller;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_locations';

    protected function getTypesPivot(): Pivot {
        return new ResellerLocationType();
    }
}
