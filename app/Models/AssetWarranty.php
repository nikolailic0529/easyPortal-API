<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Relations\HasAsset;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasDocument;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasServiceGroup;
use App\Models\Relations\HasServiceLevel;
use App\Models\Relations\HasStatusNullable;
use App\Models\Relations\HasTypeNullable;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Organization\Eloquent\OwnedByShared;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\AssetWarrantyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Asset Warranty.
 *
 * @property string               $id
 * @property string|null          $key
 * @property string               $asset_id
 * @property string|null          $type_id
 * @property string|null          $status_id
 * @property string|null          $reseller_id
 * @property string|null          $customer_id
 * @property string|null          $document_id
 * @property string|null          $document_number
 * @property string|null          $service_group_id
 * @property string|null          $service_level_id
 * @property CarbonImmutable|null $start
 * @property CarbonImmutable|null $end
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property string|null          $description
 * @property Asset                $asset
 * @property Customer|null        $customer
 * @property Document|null        $document
 * @property Reseller|null        $reseller
 * @property ServiceGroup|null    $serviceGroup
 * @property ServiceLevel|null    $serviceLevel
 * @property Status|null          $status
 * @property Type|null            $type
 * @method static AssetWarrantyFactory factory(...$parameters)
 * @method static Builder<AssetWarranty>|AssetWarranty newModelQuery()
 * @method static Builder<AssetWarranty>|AssetWarranty newQuery()
 * @method static Builder<AssetWarranty>|AssetWarranty query()
 */
class AssetWarranty extends Model implements OwnedByReseller, OwnedByShared {
    use OwnedByResellerImpl;
    use HasFactory;
    use HasAsset;
    use HasServiceGroup;
    use HasServiceLevel;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasDocument;
    use SyncBelongsToMany;
    use HasStatusNullable;
    use HasTypeNullable;

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'start' => 'date',
        'end'   => 'date',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranties';

    // <editor-fold desc="Relations">
    // =========================================================================
    public function setDocumentAttribute(Document|null $document): void {
        $this->document()->associate($document);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    public function isExtended(): bool {
        return $this->document_number !== null;
    }
    // </editor-fold>
}
