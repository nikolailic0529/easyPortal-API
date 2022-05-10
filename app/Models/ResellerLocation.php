<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasLocation;
use App\Models\Relations\HasReseller;
use App\Models\Relations\HasTypes;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Reseller Location.
 *
 * @property string                $id
 * @property string                $reseller_id
 * @property string                $location_id
 * @property int                   $customers_count
 * @property int                   $assets_count
 * @property CarbonImmutable       $created_at
 * @property CarbonImmutable       $updated_at
 * @property CarbonImmutable|null  $deleted_at
 * @property Location              $location
 * @property Reseller              $reseller
 * @property Collection<int, Type> $types
 * @method static ResellerLocationFactory factory(...$parameters)
 * @method static Builder|ResellerLocation newModelQuery()
 * @method static Builder|ResellerLocation newQuery()
 * @method static Builder|ResellerLocation query()
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
