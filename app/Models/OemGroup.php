<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasOem;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\OemGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Oem Group.
 *
 * @property string               $id
 * @property string               $oem_id
 * @property string               $key
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Oem                  $oem
 * @method static OemGroupFactory factory(...$parameters)
 * @method static Builder|OemGroup newModelQuery()
 * @method static Builder|OemGroup newQuery()
 * @method static Builder|OemGroup query()
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
