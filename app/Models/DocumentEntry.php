<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAsset;
use App\Models\Concerns\HasCurrency;
use App\Models\Concerns\HasDocument;
use App\Models\Concerns\HasProduct;
use App\Models\Concerns\HasServiceLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Document Entry.
 *
 * @property string                        $id
 * @property string                        $document_id
 * @property string                        $asset_id
 * @property string|null                   $service_level_id
 * @property string                        $product_id
 * @property string|null                   $serial_number
 * @property string|null                   $currency_id
 * @property string|null                   $net_price
 * @property string|null                   $list_price
 * @property string|null                   $discount
 * @property string|null                   $renewal
 * @property \Carbon\CarbonImmutable       $created_at
 * @property \Carbon\CarbonImmutable       $updated_at
 * @property \Carbon\CarbonImmutable|null  $deleted_at
 * @property \App\Models\Asset             $asset
 * @property \App\Models\Currency|null     $currency
 * @property \App\Models\Document          $document
 * @property \App\Models\Product           $product
 * @property \App\Models\ServiceLevel|null $serviceLevel
 * @method static \Database\Factories\DocumentEntryFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry query()
 * @mixin \Eloquent
 */
class DocumentEntry extends Model {
    use HasFactory;
    use HasAsset;
    use HasServiceLevel;
    use HasProduct;
    use HasDocument;
    use HasCurrency;

    protected const CASTS = [
        'net_price'  => 'decimal:2',
        'list_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'renewal'    => 'decimal:2',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_entries';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;
}
