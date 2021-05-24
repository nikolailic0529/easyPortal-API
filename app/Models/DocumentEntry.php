<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAsset;
use App\Models\Concerns\HasCurrency;
use App\Models\Concerns\HasDocument;
use App\Models\Concerns\HasProduct;
use App\Models\Concerns\HasService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Document Entry.
 *
 * @property string                       $id
 * @property string                       $document_id
 * @property string                       $asset_id
 * @property string                       $service_id
 * @property string                       $product_id
 * @property string|null                  $serial_number
 * @property string|null                  $currency_id
 * @property string|null                  $net_price
 * @property string|null                  $list_price
 * @property string|null                  $discount
 * @property string|null                  $renewal
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Asset            $asset
 * @property \App\Models\Currency|null    $currency
 * @property \App\Models\Document         $document
 * @property \App\Models\Product          $product
 * @property \App\Models\Product          $service
 * @method static \Database\Factories\DocumentEntryFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereListPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereNetPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereRenewal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DocumentEntry extends Model {
    use HasFactory;
    use HasAsset;
    use HasService;
    use HasProduct;
    use HasDocument;
    use HasCurrency;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_entries';
}
