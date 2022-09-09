<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\TeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Team.
 *
 * @property string               $id
 * @property string               $name
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static TeamFactory factory(...$parameters)
 * @method static Builder|Team newModelQuery()
 * @method static Builder|Team newQuery()
 * @method static Builder|Team query()
 */
class Team extends Model implements DataModel {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'teams';
}
