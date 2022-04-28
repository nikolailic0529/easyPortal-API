<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasCustomer;
use App\Models\Relations\HasLocation;
use App\Models\Relations\HasTypes;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer Location.
 *
 * @property string               $id
 * @property string               $customer_id
 * @property string               $location_id
 * @property int                  $assets_count
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Customer             $customer
 * @property Location             $location
 * @property Collection<int,Type> $types
 * @method static CustomerLocationFactory factory(...$parameters)
 * @method static Builder|CustomerLocation newModelQuery()
 * @method static Builder|CustomerLocation newQuery()
 * @method static Builder|CustomerLocation query()
 */
class CustomerLocation extends Model {
    use HasFactory;
    use HasTypes;
    use HasLocation;
    use HasCustomer;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_locations';

    protected function getTypesPivot(): Pivot {
        return new CustomerLocationType();
    }
}
