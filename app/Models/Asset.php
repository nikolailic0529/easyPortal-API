<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\SyncHasMany;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasProduct;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasStatus;
use App\Models\Relations\HasTags;
use App\Models\Relations\HasTypeNullable;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Text;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Collection;

use function count;

/**
 * Asset.
 *
 * @property string                                                                   $id
 * @property string                                                                   $oem_id
 * @property string                                                                   $product_id
 * @property string|null                                                              $type_id
 * @property string|null                                                              $reseller_id current
 * @property string|null                                                              $customer_id current
 * @property string|null                                                              $location_id current
 * @property string|null                                                              $status_id
 * @property string|null                                                              $serial_number
 * @property \Carbon\CarbonImmutable|null                                             $warranty_end
 * @property \Carbon\CarbonImmutable|null                                             $warranty_changed_at
 * @property string|null                                                              $data_quality
 * @property int                                                                      $contacts_count
 * @property int                                                                      $coverages_count
 * @property \Carbon\CarbonImmutable|null                                             $changed_at
 * @property \Carbon\CarbonImmutable                                                  $synced_at
 * @property \Carbon\CarbonImmutable                                                  $created_at
 * @property \Carbon\CarbonImmutable                                                  $updated_at
 * @property \Carbon\CarbonImmutable|null                                             $deleted_at
 * @property-read \App\Models\ChangeRequest|null                                      $changeRequest
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>            $contacts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document>      $contracts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\AssetWarranty> $contractWarranties
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Coverage>           $coverages
 * @property \App\Models\Customer|null                                                $customer
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry>      $documentEntries
 * @property \App\Models\Location|null                                                $location
 * @property \App\Models\Oem                                                          $oem
 * @property \App\Models\Product                                                      $product
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document>      $quotes
 * @property \App\Models\Reseller|null                                                $reseller
 * @property \App\Models\Status|null                                                  $status
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Tag>                $tags
 * @property \App\Models\Type|null                                                    $type
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\AssetWarranty>      $warranties
 * @property \App\Models\QuoteRequest|null                                            $quoteRequest
 * @method static \Database\Factories\AssetFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset query()
 * @mixin \Eloquent
 */
class Asset extends Model {
    use Searchable;
    use OwnedByReseller;
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
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        $this->location()->associate($location);
    }

    public function warranties(): HasMany {
        return $this->hasMany(AssetWarranty::class);
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\AssetWarranty>|array<\App\Models\AssetWarranty> $entries
     */
    public function setWarrantiesAttribute(Collection|array $warranties): void {
        $this->syncHasMany('warranties', $warranties);
        $this->warranty_end = $this->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return $warranty->document_id === null || ($warranty->document && $warranty->document->is_contract);
            })
            ->pluck('end')
            ->max();
    }

    public function contractWarranties(): HasMany {
        return $this->hasMany(AssetWarranty::class)->where(static function (Builder $builder): void {
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereNull('document_id');
            });
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereHasIn('document', static function (Builder $builder): void {
                    /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                    $builder->queryContracts();
                });
            });
        });
    }

    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }

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

    public function contracts(): HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                return $builder->queryContracts();
            });
    }

    public function quotes(): HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                return $builder->queryQuotes();
            });
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\DocumentEntry>|array<\App\Models\DocumentEntry> $entries
     */
    public function setDocumentEntriesAttribute(Collection|array $entries): void {
        $this->syncHasMany('documentEntries', $entries);
    }

    protected function getTagsPivot(): Pivot {
        return new AssetTag();
    }

    public function coverages(): BelongsToMany {
        $pivot = new AssetCoverage();

        return $this
            ->belongsToMany(Coverage::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Coverage>|array<\App\Models\Coverage> $coverages
     */
    public function setCoveragesAttribute(Collection|array $coverages): void {
        $this->syncBelongsToMany('coverages', $coverages);
        $this->coverages_count = count($this->coverages);
    }

    public function quoteRequest(): HasOneThrough {
        $request = new QuoteRequest();

        return $this->hasOneThrough(QuoteRequest::class, QuoteRequestAsset::class, 'asset_id', 'id', 'id', 'request_id')
            ->whereNull($request->qualifyColumn($request->getDeletedAtColumn()))
            ->orderByDesc($request->qualifyColumn($request->getCreatedAtColumn()));
    }

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
            'product'       => [
                'sku'  => new Text('product.sku', true),
                'name' => new Text('product.name', true),
            ],
            'customer'      => [
                'name' => new Text('customer.name', true),
            ],
        ];
    }
    // </editor-fold>
}
