<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Distributor.
 *
 * @property string                       $id
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Database\Factories\DistributorFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Distributor whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Distributor extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'distributors';
}
