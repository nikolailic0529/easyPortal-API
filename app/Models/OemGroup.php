<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasOem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem Group.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $key
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Oem              $oem
 * @method static \Database\Factories\OemGroupFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OemGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OemGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OemGroup query()
 * @mixin \Eloquent
 */
class OemGroup extends Model {
    use HasFactory;
    use HasOem;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'oem_groups';
}
