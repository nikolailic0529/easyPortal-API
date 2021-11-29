<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Distributor.
 *
 * @property string                       $id
 * @property string                       $name
 * @property \Carbon\CarbonImmutable|null $changed_at
 * @property \Carbon\CarbonImmutable      $synced_at
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\DistributorFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor query()
 * @mixin \Eloquent
 */
class Distributor extends Model {
    use HasFactory;

    protected const CASTS = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'distributors';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
