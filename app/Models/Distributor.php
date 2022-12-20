<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\DistributorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Distributor.
 *
 * @property string               $id
 * @property string               $name
 * @property CarbonImmutable|null $changed_at
 * @property CarbonImmutable|null $synced_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static DistributorFactory factory(...$parameters)
 * @method static Builder<Distributor>|Distributor newModelQuery()
 * @method static Builder<Distributor>|Distributor newQuery()
 * @method static Builder<Distributor>|Distributor query()
 */
class Distributor extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'distributors';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
    ];
}
