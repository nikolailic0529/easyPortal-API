<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasOem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Product.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $sku
 * @property string                       $name
 * @property \Carbon\CarbonImmutable|null $eol
 * @property \Carbon\CarbonImmutable|null $eos
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Oem              $oem
 * @method static \Database\Factories\ProductFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @mixin \Eloquent
 */
class Product extends Model {
    use HasFactory;
    use HasOem;

    protected const CASTS = [
        'eol' => 'date',
        'eos' => 'date',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
