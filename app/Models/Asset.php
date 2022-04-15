<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasProduct;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasStatus;
use App\Models\Relations\HasTags;
use App\Models\Relations\HasTypeNullable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\AssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Collection as BaseCollection;

use function count;

/**
 * Asset.
 *
 * @property string                              $id
 * @property string                              $oem_id
 * @property string                              $product_id
 * @property string|null                         $type_id
 * @property string|null                         $reseller_id current
 * @property string|null                         $customer_id current
 * @property string|null                         $location_id current
 * @property string|null                         $status_id
 * @property string|null                         $serial_number
 * @property CarbonImmutable|null                $warranty_end
 * @property CarbonImmutable|null                $warranty_changed_at
 * @property string|null                         $data_quality
 * @property int                                 $contacts_count
 * @property int                                 $coverages_count
 * @property CarbonImmutable|null                $changed_at
 * @property CarbonImmutable                     $synced_at
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
 * @property Oem                                 $oem
 * @property Product                             $product
 * @property-read Collection<int, Document>      $quotes
 * @property Reseller|null                       $reseller
 * @property Status|null                         $status
 * @property Collection<int, Tag>                $tags
 * @property Type|null                           $type
 * @property Collection<int, AssetWarranty>      $warranties
 * @property QuoteRequest|null                   $quoteRequest
 * @method static AssetFactory factory(...$parameters)
 * @method static Builder|Asset newModelQuery()
 * @method static Builder|Asset newQuery()
 * @method static Builder|Asset query()
 * @mixin Eloquent
 */
class Asset extends Model implements OwnedByOrganization, Searchable {
    use SearchableImpl;
    use OwnedByResellerImpl;
    use HasFactory;
    use SyncHasMany;
    use HasOem;
    use HasTypeNullable;
    use HasProduct;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasStatus;
    use HasContacts;
    use HasTags;

    protected const CASTS = [
        'changed_at'          => 'datetime',
        'synced_at'           => 'datetime',
        'warranty_end'        => 'date',
        'warranty_changed_at' => 'datetime',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'assets';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    // <editor-fold desc="Relations">
    // =========================================================================
    #[CascadeDelete(false)]
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        $this->location()->associate($location);
    }

    #[CascadeDelete(true)]
    public function warranties(): HasMany {
        return $this->hasMany(AssetWarranty::class);
    }

    /**
     * @param BaseCollection<int,AssetWarranty>|array<AssetWarranty> $warranties
     */
    public function setWarrantiesAttribute(BaseCollection|array $warranties): void {
        $this->syncHasMany('warranties', $warranties);
        $this->warranty_end = $this->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return $warranty->document_id === null || ($warranty->document && $warranty->document->is_contract);
            })
            ->pluck('end')
            ->max();
    }

    #[CascadeDelete(false)]
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

    #[CascadeDelete(false)]
    protected function documents(): HasManyThrough {
        return $this->hasManyThrough(
            Document::class,
            DocumentEntry::class,
            null,
            (new Document())->getKeyName(),
            null,
            'document_id',
        );
    }

    #[CascadeDelete(false)]
    public function contracts(): HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var Builder<Document> $builder */
                return $builder->queryContracts();
            });
    }

    #[CascadeDelete(false)]
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

    #[CascadeDelete(true)]
    public function coverages(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Coverage::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param BaseCollection|array<Coverage> $coverages
     */
    public function setCoveragesAttribute(BaseCollection|array $coverages): void {
        $this->syncBelongsToMany('coverages', $coverages);
        $this->coverages_count = count($this->coverages);
    }

    #[CascadeDelete(false)]
    public function quoteRequest(): HasOneThrough {
        $request = new QuoteRequest();

        return $this->hasOneThrough(QuoteRequest::class, QuoteRequestAsset::class, 'asset_id', 'id', 'id', 'request_id')
            ->whereNull($request->qualifyColumn($request->getDeletedAtColumn()))
            ->orderByDesc($request->qualifyColumn($request->getCreatedAtColumn()));
    }

    #[CascadeDelete(false)]
    public function changeRequest(): HasOne {
        $request = new ChangeRequest();

        return $this->hasOne(ChangeRequest::class, 'object_id')
            ->whereNull($request->qualifyColumn($request->getDeletedAtColumn()))
            ->orderByDesc($request->qualifyColumn($request->getCreatedAtColumn()));
    }

    //</editor-fold>

    // <editor-fold desc="Searchable">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'serial_number' => new Text('serial_number', true),
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
}
