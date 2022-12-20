<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Coverage;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Tag;
use App\Models\Data\Type;
use App\Models\Relations\HasChangeRequests;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasOemNullable;
use App\Models\Relations\HasProduct;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasStatus;
use App\Models\Relations\HasTags;
use App\Models\Relations\HasTypeNullable;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

use function count;

/**
 * Asset.
 *
 * @property string                              $id
 * @property string|null                         $oem_id
 * @property string|null                         $product_id
 * @property string|null                         $type_id
 * @property string|null                         $reseller_id current
 * @property string|null                         $customer_id current
 * @property string|null                         $location_id current
 * @property string|null                         $status_id
 * @property string|null                         $serial_number
 * @property string|null                         $nickname
 * @property CarbonImmutable|null                $warranty_end
 * @property CarbonImmutable|null                $warranty_changed_at
 * @property string|null                         $warranty_service_group_id
 * @property string|null                         $warranty_service_level_id
 * @property string|null                         $data_quality
 * @property CarbonImmutable|null                $eosl
 * @property int|null                            $contracts_active_quantity
 * @property int                                 $contacts_count
 * @property int                                 $coverages_count
 * @property CarbonImmutable|null                $changed_at
 * @property CarbonImmutable|null                $synced_at
 * @property CarbonImmutable                     $created_at
 * @property CarbonImmutable                     $updated_at
 * @property CarbonImmutable|null                $deleted_at
 * @property-read ChangeRequest|null             $changeRequest
 * @property Collection<int, Contact>            $contacts
 * @property-read Collection<int, Document>      $contracts
 * @property-read Collection<int, AssetWarranty> $contractWarranties
 * @property Collection<int, Coverage>           $coverages
 * @property Customer|null                       $customer
 * @property Location|null                       $location
 * @property Oem|null                            $oem
 * @property Product|null                        $product
 * @property-read Collection<int, Document>      $quotes
 * @property Reseller|null                       $reseller
 * @property Status|null                         $status
 * @property Collection<int, Tag>                $tags
 * @property Type|null                           $type
 * @property AssetWarranty|null                  $warranty
 * @property Collection<int, AssetWarranty>      $warranties
 * @property ServiceGroup|null                   $warrantyServiceGroup
 * @property ServiceLevel|null                   $warrantyServiceLevel
 * @property QuoteRequest|null                   $quoteRequest
 * @method static AssetFactory factory(...$parameters)
 * @method static Builder<Asset>|Asset newModelQuery()
 * @method static Builder<Asset>|Asset newQuery()
 * @method static Builder<Asset>|Asset query()
 */
class Asset extends Model implements OwnedByReseller, Searchable {
    use SearchableImpl;
    use OwnedByResellerImpl;
    use HasFactory;
    use SyncHasMany;
    use HasOemNullable;
    use HasTypeNullable;
    use HasProduct;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasStatus;
    use HasContacts;
    use HasTags;
    use HasChangeRequests;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'assets';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'changed_at'          => 'datetime',
        'synced_at'           => 'datetime',
        'warranty_end'        => 'date',
        'warranty_changed_at' => 'datetime',
        'eosl'                => 'date',
    ];

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return BelongsTo<Location, self>
     */
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        $this->location()->associate($location);
    }

    /**
     * @return HasMany<AssetWarranty>
     */
    public function warranties(): HasMany {
        return $this->hasMany(AssetWarranty::class);
    }

    /**
     * @param Collection<int,AssetWarranty> $warranties
     */
    public function setWarrantiesAttribute(Collection $warranties): void {
        $this->syncHasMany('warranties', $warranties);
        $this->warranty = self::getLastWarranty($this->warranties);
    }

    /**
     * @return HasMany<AssetWarranty>
     */
    public function contractWarranties(): HasMany {
        return $this->hasMany(AssetWarranty::class)->where(static function (Builder $builder): void {
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereNull('document_id');
            });
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereHasIn('document', static function (Builder $builder): void {
                    /** @var Builder<Document> $builder */
                    $builder->queryContracts();
                });
            });
        });
    }

    /**
     * @return HasManyThrough<Document>
     */
    public function documents(): HasManyThrough {
        return $this
            ->hasManyThrough(
                Document::class,
                DocumentEntry::class,
                null,
                (new Document())->getKeyName(),
                null,
                'document_id',
            )
            ->distinct();
    }

    /**
     * @return HasManyThrough<Document>
     */
    public function contracts(): HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var Builder<Document> $builder */
                return $builder->queryContracts();
            });
    }

    /**
     * @return HasManyThrough<Document>
     */
    public function quotes(): HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var Builder<Document> $builder */
                return $builder->queryQuotes();
            });
    }

    protected function getTagsPivot(): Pivot {
        return new AssetTag();
    }

    /**
     * @return BelongsToMany<Coverage>
     */
    public function coverages(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Coverage::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param Collection<int, Coverage> $coverages
     */
    public function setCoveragesAttribute(Collection $coverages): void {
        $this->syncBelongsToMany('coverages', $coverages);
        $this->coverages_count = count($this->coverages);
    }

    /**
     * @return HasOneThrough<QuoteRequest>
     */
    public function quoteRequest(): HasOneThrough {
        $request = new QuoteRequest();

        return $this->hasOneThrough(QuoteRequest::class, QuoteRequestAsset::class, 'asset_id', 'id', 'id', 'request_id')
            ->whereNull($request->qualifyColumn($request->getDeletedAtColumn()))
            ->latest();
    }

    /**
     * @return HasOne<ChangeRequest>
     */
    public function changeRequest(): HasOne {
        $request = new ChangeRequest();

        return $this->hasOne(ChangeRequest::class, 'object_id')
            ->whereNull($request->qualifyColumn($request->getDeletedAtColumn()))
            ->latest();
    }

    /**
     * @return HasOne<AssetWarranty>
     */
    public function warranty(): HasOne {
        return $this
            ->hasOne(AssetWarranty::class)
            ->orderByDesc('end')
            ->orderBy(
                (new AssetWarranty())->getKeyName(),
            );
    }

    public function setWarrantyAttribute(?AssetWarranty $warranty): void {
        // Properties
        $this->warranty_end              = $warranty->end ?? null;
        $this->warranty_service_group_id = $warranty->service_group_id ?? null;
        $this->warranty_service_level_id = $warranty->service_level_id ?? null;

        // Relations
        $relations = [
            'warrantyServiceGroup' => 'serviceGroup',
            'warrantyServiceLevel' => 'serviceLevel',
        ];

        foreach ($relations as $k => $v) {
            if ($warranty && $warranty->relationLoaded($v)) {
                $this->setRelation($k, $warranty->getRelation($v));
            } else {
                $this->unsetRelation($k);
            }
        }
    }

    /**
     * @return BelongsTo<ServiceGroup, self>
     */
    public function warrantyServiceGroup(): BelongsTo {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function setWarrantyServiceGroupAttribute(?ServiceGroup $group): void {
        $this->warrantyServiceGroup()->associate($group);
    }

    /**
     * @return BelongsTo<ServiceLevel, self>
     */
    public function warrantyServiceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    public function setWarrantyServiceLevelAttribute(?ServiceLevel $level): void {
        $this->warrantyServiceLevel()->associate($level);
    }
    //</editor-fold>

    // <editor-fold desc="Searchable">
    // =========================================================================
    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'serial_number' => new Text('serial_number', true),
            'nickname'      => new Text('nickname', true),
            'product'       => new Relation('product', [
                'sku'  => new Text('sku', true),
                'name' => new Text('name', true),
            ]),
            'customer'      => new Relation('customer', [
                'name' => new Text('name', true),
            ]),
        ];
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param Collection<array-key, AssetWarranty> $warranties
     */
    public static function getLastWarranty(Collection $warranties): ?AssetWarranty {
        $lastWarranty = null;
        $warranties   = $warranties->sort(static function (AssetWarranty $a, AssetWarranty $b): int {
            return $b->end <=> $a->end
                ?: $a->getKey() <=> $b->getKey();
        });

        foreach ($warranties as $warranty) {
            if (self::getLastWarrantyIsVisibleWarranty($warranty)) {
                $lastWarranty = $warranty;
                break;
            }
        }

        return $lastWarranty;
    }

    private static function getLastWarrantyIsVisibleWarranty(AssetWarranty $warranty): bool {
        // Extended?
        if (!$warranty->isExtended()) {
            return true;
        }

        // Document?
        $document  = $warranty->document;
        $isVisible = $document
            && $document->is_visible
            && $document->is_contract;

        if (!$isVisible) {
            return false;
        }

        // Ok
        return true;
    }

    // </editor-fold>
}
