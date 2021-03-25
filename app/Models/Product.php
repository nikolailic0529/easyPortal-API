<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasOem;
use App\Models\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Product.
 *
 * @property string                        $id
 * @property string                        $oem_id
 * @property \App\Models\Enums\ProductType $type
 * @property string                        $sku
 * @property string                        $name
 * @property \Carbon\CarbonImmutable|null  $eol
 * @property \Carbon\CarbonImmutable|null  $eos
 * @property \Carbon\CarbonImmutable       $created_at
 * @property \Carbon\CarbonImmutable       $updated_at
 * @property \Carbon\CarbonImmutable|null  $deleted_at
 * @property \App\Models\Oem               $oem
 * @method static \Database\Factories\ProductFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereEol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereEos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Product extends Model {
    use HasFactory;
    use HasOem;

    protected const CASTS = [
        'eol'  => 'date',
        'eos'  => 'date',
        'type' => ProductType::class,
    ];

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
    protected $casts = self::CASTS + parent::CASTS;
}
