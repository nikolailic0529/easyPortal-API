<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAsset;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasDocument;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasServiceGroup;
use App\Models\Relations\HasStatusNullable;
use App\Models\Relations\HasTypeNullable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Organization\Eloquent\OwnedByShared;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\AssetWarrantyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Asset Warranty.
 *
 * @property string                       $id
 * @property string                       $asset_id
 * @property string|null                  $type_id
 * @property string|null                  $status_id
 * @property string|null                  $reseller_id
 * @property string|null                  $customer_id
 * @property string|null                  $document_id
 * @property string|null                  $document_number
 * @property string|null                  $service_group_id
 * @property CarbonImmutable|null         $start
 * @property CarbonImmutable|null         $end
 * @property CarbonImmutable              $created_at
 * @property CarbonImmutable              $updated_at
 * @property CarbonImmutable|null         $deleted_at
 * @property string|null                  $description
 * @property Asset                        $asset
 * @property Customer|null                $customer
 * @property Document|null                $document
 * @property Reseller|null                $reseller
 * @property ServiceGroup|null            $serviceGroup
 * @property Collection<int,ServiceLevel> $serviceLevels
 * @property Status|null                  $status
 * @property Type|null                    $type
 * @method static AssetWarrantyFactory factory(...$parameters)
 * @method static Builder|AssetWarranty newModelQuery()
 * @method static Builder|AssetWarranty newQuery()
 * @method static Builder|AssetWarranty query()
 */
class AssetWarranty extends Model implements OwnedByOrganization, OwnedByShared {
    use OwnedByResellerImpl;
    use HasFactory;
    use HasAsset;
    use HasServiceGroup;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasDocument;
    use SyncBelongsToMany;
    use HasStatusNullable;
    use HasTypeNullable;

    protected const CASTS = [
        'start' => 'date',
        'end'   => 'date',
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranties';

    public function setDocumentAttribute(Document|null $document): void {
        $this->document()->associate($document);
    }

    #[CascadeDelete(true)]
    public function serviceLevels(): BelongsToMany {
        $pivot = new AssetWarrantyServiceLevel();

        return $this
            ->belongsToMany(ServiceLevel::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param BaseCollection|array<ServiceLevel> $levels
     */
    public function setServiceLevelsAttribute(BaseCollection|array $levels): void {
        $this->syncBelongsToMany('serviceLevels', $levels);
    }
}
