<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasOem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Service Group.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $sku
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Oem              $oem
 * @method static \Database\Factories\ServiceGroupFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ServiceGroup query()
 * @mixin \Eloquent
 */
class ServiceGroup extends Model {
    use HasFactory;
    use HasOem;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'service_groups';
}
