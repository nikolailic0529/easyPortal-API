<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasServiceGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Service Level.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $service_group_id
 * @property string                       $sku
 * @property string                       $name
 * @property string                       $description
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Oem              $oem
 * @property \App\Models\ServiceGroup     $serviceGroup
 * @method static \Database\Factories\ServiceLevelFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceLevel query()
 * @mixin \Eloquent
 */
class ServiceLevel extends Model {
    use HasFactory;
    use HasOem;
    use HasServiceGroup {
        setServiceGroupAttribute as private setServiceGroupAttributeNullable;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'service_levels';

    public function setServiceGroupAttribute(ServiceGroup $group): void {
        $this->setServiceGroupAttributeNullable($group);
    }
}
