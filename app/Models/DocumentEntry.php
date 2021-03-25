<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAsset;
use App\Models\Concerns\HasDocument;
use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasProduct;
use App\Models\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Document Entry.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $document_id
 * @property string                       $asset_id
 * @property string                       $product_id
 * @property int                          $quantity
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Asset            $asset
 * @property \App\Models\Document         $document
 * @property \App\Models\Oem              $oem
 * @property \App\Models\Product          $product
 * @method static \Database\Factories\DocumentEntryFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereOemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DocumentEntry whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DocumentEntry extends Model {
    use HasFactory;
    use HasOem;
    use HasAsset;
    use HasProduct;
    use HasDocument;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_entries';

    /**
     * @inheritdoc
     */
    protected function getValidProductTypes(): array {
        return [ProductType::service()];
    }
}
