<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssetNullable;
use App\Models\Relations\HasCurrency;
use App\Models\Relations\HasDocument;
use App\Models\Relations\HasLanguage;
use App\Models\Relations\HasProduct;
use App\Models\Relations\HasServiceGroup;
use App\Models\Relations\HasServiceLevel;
use App\Models\Relations\HasTypeNullable;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Document Entry.
 *
 * @property string               $id
 * @property string               $document_id
 * @property string|null          $asset_id
 * @property string|null          $asset_type_id
 * @property string|null          $service_group_id
 * @property string|null          $service_level_id
 * @property string|null          $product_id
 * @property string|null          $serial_number
 * @property CarbonImmutable|null $start
 * @property CarbonImmutable|null $end
 * @property string|null          $currency_id
 * @property string|null          $list_price
 * @property string|null          $monthly_list_price
 * @property string|null          $monthly_retail_price
 * @property string|null          $renewal
 * @property string|null          $oem_said
 * @property string|null          $oem_sar_number
 * @property string|null          $environment_id
 * @property string|null          $equipment_number
 * @property string|null          $language_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Asset|null           $asset
 * @property Type|null            $assetType
 * @property Currency|null        $currency
 * @property Document             $document
 * @property Language|null        $language
 * @property Product|null         $product
 * @property ServiceGroup|null    $serviceGroup
 * @property ServiceLevel|null    $serviceLevel
 * @method static DocumentEntryFactory factory(...$parameters)
 * @method static Builder|DocumentEntry newModelQuery()
 * @method static Builder|DocumentEntry newQuery()
 * @method static Builder|DocumentEntry query()
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

    use HasTypeNullable {
        type as assetType;
        setTypeAttribute as setAssetTypeAttribute;
    }

    protected const CASTS = [
        'monthly_retail_price' => 'decimal:2',
        'monthly_list_price'   => 'decimal:2',
        'list_price'           => 'decimal:2',
        'renewal'              => 'decimal:2',
        'start'                => 'date',
        'end'                  => 'date',
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
