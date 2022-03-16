<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasCustomer;
use App\Models\Relations\HasLocation;
use App\Models\Relations\HasTypes;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer Location.
 *
 * @property string                       $id
 * @property string                       $customer_id
 * @property string                       $location_id
 * @property int                          $assets_count
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Customer         $customer
 * @property \App\Models\Location         $location
 * @property Collection<int,Type>         $types
 * @method static \Database\Factories\CustomerLocationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation query()
 * @mixin \Eloquent
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
