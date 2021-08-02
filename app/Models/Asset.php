<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasContacts;
use App\Models\Concerns\Relations\HasCustomer;
use App\Models\Concerns\Relations\HasOem;
use App\Models\Concerns\Relations\HasProduct;
use App\Models\Concerns\Relations\HasReseller;
use App\Models\Concerns\Relations\HasStatus;
use App\Models\Concerns\Relations\HasTags;
use App\Models\Concerns\Relations\HasTypeNullable;
use App\Models\Concerns\SyncHasMany;
use App\Services\Organization\Eloquent\OwnedByReseller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function in_array;
use function is_null;
use function sprintf;

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
 * @property string|null                                                              $data_quality
 * @property int                                                                      $contacts_count
 * @property \Carbon\CarbonImmutable|null                                             $changed_at
 * @property \Carbon\CarbonImmutable                                                  $created_at
 * @property \Carbon\CarbonImmutable                                                  $updated_at
 * @property \Carbon\CarbonImmutable|null                                             $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>            $contacts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\AssetWarranty> $contractWarranties
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Coverage>           $coverages
 * @property \App\Models\Customer|null                                                $customer
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry>      $documentEntries
 * @property \App\Models\Location|null                                                $location
 * @property \App\Models\Oem                                                          $oem
 * @property \App\Models\Product                                                      $product
 * @property \App\Models\Reseller|null                                                $reseller
 * @property \App\Models\Status|null                                                  $status
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Tag>                $tags
 * @property \App\Models\Type|null                                                    $type
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\AssetWarranty>      $warranties
 * @method static \Database\Factories\AssetFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset query()
 * @mixin \Eloquent
 */
class Asset extends Model {
    use OwnedByReseller;
    use HasFactory;
    use SyncHasMany;
    use HasOem;
    use HasTypeNullable;
    use HasProduct;
    use HasReseller;
    use HasCustomer;
    use HasStatus;
    use HasContacts;
    use HasTags;

    protected const CASTS = [
        'changed_at' => 'datetime',
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

    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        // Assert may be located on
        // - customer location
        // - reseller location
        // - own location
        if ($location) {
            $asset       = (new Asset())->getMorphClass();
            $customer    = (new Customer())->getMorphClass();
            $reseller    = (new Reseller())->getMorphClass();
            $isIdMatch   = is_null($location->object_id)
                || in_array($location->object_id, [$this->customer_id, $this->reseller_id], true);
            $isTypeMatch = in_array($location->object_type, [$asset, $customer, $reseller], true);

            if (!$isIdMatch || !$isTypeMatch) {
                throw new InvalidArgumentException(sprintf(
                    'Location must be related to the `%s` or `%s` or `%s` but it related to `%s#%s`.',
                    $customer.($this->customer_id ? "#{$this->customer_id}" : ''),
                    $reseller.($this->reseller_id ? "#{$this->reseller_id}" : ''),
                    $asset,
                    $location->object_type,
                    $location->object_id,
                ));
            }
        }

        // Set
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
    }

    public function contractWarranties(): HasMany {
        return $this->hasMany(AssetWarranty::class)->where(static function (Builder $builder): void {
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereNull('document_id');
            });
            $builder->orWhere(static function (Builder $builder): void {
                $builder->whereHas('document', static function (Builder $builder): void {
                    /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                    $builder->queryContracts();
                });
            });
        });
    }

    public function documentEntries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
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
    }

    public function requests(): BelongsToMany {
        $pivot = new QuoteRequestAsset();
        return $this
            ->belongsToMany(QuoteRequest::class, $pivot->getTable(), 'asset_id', 'request_id')
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    public function getQuoteRequestAttribute(): ?QuoteRequest {
        $request = new QuoteRequest();
        return $this->requests()
            ->orderByDesc($request->qualifyColumn('created_at'))
            ->first();
    }
}
