<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Casts\DocumentPrice;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Product;
use App\Models\Data\ProductGroup;
use App\Models\Data\ProductLine;
use App\Models\Data\Psp;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Type;
use App\Models\Relations\HasAssetNullable;
use App\Models\Relations\HasCurrency;
use App\Models\Relations\HasDocument;
use App\Models\Relations\HasLanguage;
use App\Models\Relations\HasProduct;
use App\Models\Relations\HasServiceGroup;
use App\Models\Relations\HasServiceLevel;
use App\Utils\Eloquent\Casts\Origin;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * Document Entry.
 *
 * @property string               $id
 * @property string|null          $key
 * @property string               $document_id
 * @property string|null          $asset_id
 * @property string|null          $asset_type_id
 * @property string|null          $service_group_id
 * @property string|null          $service_level_id
 * @property string|null          $product_id
 * @property string|null          $product_line_id
 * @property string|null          $product_group_id
 * @property string|null          $serial_number
 * @property CarbonImmutable|null $start
 * @property CarbonImmutable|null $end
 * @property string|null          $currency_id
 * @property-read string|null     $list_price
 * @property string|null          $list_price_origin
 * @property-read string|null     $monthly_list_price
 * @property string|null          $monthly_list_price_origin
 * @property-read string|null     $monthly_retail_price
 * @property string|null          $monthly_retail_price_origin
 * @property-read string|null     $renewal
 * @property string|null          $renewal_origin
 * @property string|null          $oem_said
 * @property string|null          $oem_sar_number
 * @property string|null          $psp_id
 * @property string|null          $environment_id
 * @property string|null          $equipment_number
 * @property string|null          $language_id
 * @property string|null          $hash
 * @property CarbonImmutable|null $removed_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Asset|null           $asset
 * @property Type|null            $assetType
 * @property Currency|null        $currency
 * @property Document|null        $document
 * @property Language|null        $language
 * @property Product|null         $product
 * @property ProductLine|null     $productLine
 * @property ProductGroup|null    $productGroup
 * @property Psp|null             $psp
 * @property ServiceGroup|null    $serviceGroup
 * @property ServiceLevel|null    $serviceLevel
 * @method static DocumentEntryFactory factory(...$parameters)
 * @method static Builder<DocumentEntry>|DocumentEntry newModelQuery()
 * @method static Builder<DocumentEntry>|DocumentEntry newQuery()
 * @method static Builder<DocumentEntry>|DocumentEntry query()
 */
class DocumentEntry extends Model {
    use HasFactory;
    use HasAssetNullable;
    use HasServiceGroup;
    use HasServiceLevel;
    use HasProduct;
    use HasDocument;
    use HasCurrency;
    use HasLanguage;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'document_entries';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'monthly_retail_price'        => DocumentPrice::class,
        'monthly_retail_price_origin' => Origin::class,
        'monthly_list_price'          => DocumentPrice::class,
        'monthly_list_price_origin'   => Origin::class,
        'list_price'                  => DocumentPrice::class,
        'list_price_origin'           => Origin::class,
        'renewal'                     => DocumentPrice::class,
        'renewal_origin'              => Origin::class,
        'start'                       => 'date',
        'end'                         => 'date',
        'removed_at'                  => 'datetime',
    ];

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return BelongsTo<Type, self>
     */
    public function assetType(): BelongsTo {
        return $this->belongsTo(Type::class);
    }

    public function setAssetTypeAttribute(?Type $type): void {
        if ($type && $type->object_type !== (new Asset())->getMorphClass()) {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be `%s`, `%s` given.',
                $this->getMorphClass(),
                $type->object_type,
            ));
        }

        $this->assetType()->associate($type);
    }

    /**
     * @return BelongsTo<ProductLine, self>
     */
    public function productLine(): BelongsTo {
        return $this->belongsTo(ProductLine::class);
    }

    public function setProductLineAttribute(?ProductLine $line): void {
        $this->productLine()->associate($line);
    }

    /**
     * @return BelongsTo<ProductGroup, self>
     */
    public function productGroup(): BelongsTo {
        return $this->belongsTo(ProductGroup::class);
    }

    public function setProductGroupAttribute(?ProductGroup $group): void {
        $this->productGroup()->associate($group);
    }

    /**
     * @return BelongsTo<Psp, self>
     */
    public function psp(): BelongsTo {
        return $this->belongsTo(Psp::class);
    }

    public function setPspAttribute(?Psp $psp): void {
        $this->psp()->associate($psp);
    }
    // </editor-fold>
}
