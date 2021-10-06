<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\CascadeDeletes\CascadeDeletable;
use App\Models\Concerns\Relations\HasCustomer;
use App\Models\Concerns\Relations\HasLocation;
use App\Models\Concerns\Relations\HasResellers;
use App\Models\Concerns\Relations\HasTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;

use function in_array;

/**
 * Customer Location.
 *
 * @property string                                                              $id
 * @property string                                                              $customer_id
 * @property string                                                              $location_id
 * @property int                                                                 $assets_count
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \App\Models\Customer                                                $customer
 * @property \App\Models\Location                                                $location
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Reseller> $resellers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type>          $types
 * @method static \Database\Factories\CustomerLocationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation query()
 * @mixin \Eloquent
 */
class CustomerLocation extends Model implements CascadeDeletable {
    use HasFactory;
    use HasTypes;
    use HasLocation;
    use HasCustomer;
    use HasResellers;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_locations';

    protected function getTypesPivot(): Pivot {
        return new CustomerLocationType();
    }

    protected function getResellersPivot(): Pivot {
        return new LocationReseller();
    }

    protected function getResellersParentKey(): ?string {
        return 'location_id';
    }

    protected function getResellersForeignPivotKey(): ?string {
        return 'location_id';
    }

    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool {
        return $default && !in_array($name, ['resellers'], true);
    }
}
