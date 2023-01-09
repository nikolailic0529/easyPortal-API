<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Casts\DocumentPrice;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Oem;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Relations\HasChangeRequests;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCurrency;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasLanguage;
use App\Models\Relations\HasOemNullable;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasStatuses;
use App\Models\Relations\HasTypeNullable;
use App\Models\Scopes\DocumentStatusScope;
use App\Models\Scopes\DocumentStatusScopeImpl;
use App\Models\Scopes\DocumentTypeContractScope;
use App\Models\Scopes\DocumentTypeQueries;
use App\Models\Scopes\DocumentTypeQuoteType;
use App\Models\Scopes\DocumentTypeScopeImpl;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Date;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\Casts\Origin;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use function count;

/**
 * Document.
 *
 * @property string                         $id
 * @property string|null                    $oem_id
 * @property string|null                    $oem_said
 * @property string|null                    $oem_group_id
 * @property string|null                    $type_id
 * @property string|null                    $customer_id
 * @property string|null                    $reseller_id
 * @property string|null                    $distributor_id
 * @property string|null                    $number
 * @property CarbonImmutable|null           $start
 * @property CarbonImmutable|null           $end
 * @property-read string|null               $price
 * @property string|null                    $price_origin
 * @property string|null                    $currency_id
 * @property string|null                    $language_id
 * @property string|null                    $oem_amp_id
 * @property string|null                    $oem_sar_number
 * @property int                            $assets_count
 * @property int                            $entries_count
 * @property int                            $contacts_count
 * @property int                            $statuses_count
 * @property string|null                    $hash
 * @property CarbonImmutable|null           $changed_at
 * @property CarbonImmutable|null           $synced_at
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property Collection<int, Contact>       $contacts
 * @property Currency|null                  $currency
 * @property Customer|null                  $customer
 * @property Distributor|null               $distributor
 * @property-read bool                      $is_hidden
 * @property-read bool                      $is_visible
 * @property-read bool                      $is_contract
 * @property-read bool                      $is_quote
 * @property-read Collection<int, Asset>    $assets
 * @property Collection<int, DocumentEntry> $entries
 * @property Language|null                  $language
 * @property-read Collection<int, Note>     $notes
 * @property Oem|null                       $oem
 * @property OemGroup|null                  $oemGroup
 * @property Reseller|null                  $reseller
 * @property Collection<int, Status>        $statuses
 * @property Type|null                      $type
 * @method static DocumentFactory factory(...$parameters)
 * @method static Builder<Document>|Document newModelQuery()
 * @method static Builder<Document>|Document newQuery()
 * @method static Builder<Document>|Document query()
 */
class Document extends Model implements OwnedByReseller, Searchable {
    use HasFactory;
    use SearchableImpl;
    use OwnedByResellerImpl;
    use HasOemNullable;
    use HasTypeNullable;
    use HasStatuses;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasCurrency;
    use HasLanguage;
    use HasContacts;
    use HasChangeRequests;
    use SyncHasMany;
    use DocumentTypeScopeImpl;
    use DocumentStatusScopeImpl;

    /**
     * @use DocumentTypeQueries<static>
     */
    use DocumentTypeQueries;

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'changed_at'   => 'datetime',
        'synced_at'    => 'datetime',
        'price'        => DocumentPrice::class,
        'price_origin' => Origin::class,
        'start'        => 'date',
        'end'          => 'date',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'documents';

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return HasMany<DocumentEntry>
     */
    public function entries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }

    /**
     * @param Collection<int, DocumentEntry> $entries
     */
    public function setEntriesAttribute(Collection $entries): void {
        $this->syncHasMany('entries', $entries);
        $this->entries_count = count($this->entries);
        $this->assets_count  = 0
            + $this->entries
                ->map(static function (DocumentEntry $entry): ?string {
                    return $entry->asset_id;
                })
                ->filter()
                ->unique()
                ->count()
            + $this->entries
                ->filter(static function (DocumentEntry $entry): bool {
                    return $entry->asset_id === null;
                })
                ->count();
    }

    /**
     * @return BelongsTo<Distributor, self>
     */
    public function distributor(): BelongsTo {
        return $this->belongsTo(Distributor::class);
    }

    public function setDistributorAttribute(?Distributor $distributor): void {
        $this->distributor()->associate($distributor);
    }

    /**
     * @return BelongsTo<OemGroup, self>
     */
    public function oemGroup(): BelongsTo {
        return $this->belongsTo(OemGroup::class);
    }

    public function setOemGroupAttribute(?OemGroup $group): void {
        $this->oemGroup()->associate($group);
    }

    /**
     * @return HasMany<Note>
     */
    public function notes(): HasMany {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasManyThrough<Asset>
     */
    public function assets(): HasManyThrough {
        return $this
            ->hasManyThrough(
                Asset::class,
                DocumentEntry::class,
                null,
                (new Asset())->getKeyName(),
                null,
                'asset_id',
            )
            ->distinct();
    }

    protected function getStatusesPivot(): Pivot {
        return new DocumentStatus();
    }
    // </editor-fold>

    // <editor-fold desc="Attributes">
    // =========================================================================
    public function getIsContractAttribute(): bool {
        return Container::getInstance()->make(DocumentTypeContractScope::class)->isContractType($this->type_id);
    }

    public function getIsQuoteAttribute(): bool {
        return Container::getInstance()->make(DocumentTypeQuoteType::class)->isQuoteType($this->type_id);
    }

    public function getIsHiddenAttribute(): bool {
        return Container::getInstance()->make(DocumentStatusScope::class)->isHidden($this->statuses);
    }

    public function getIsVisibleAttribute(): bool {
        return !$this->getIsHiddenAttribute();
    }
    // </editor-fold>

    // <editor-fold desc="Searchable">
    // =========================================================================
    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'number'   => new Text('number', true),
            'start'    => new Date('start'),
            'end'      => new Date('end'),
            'customer' => new Relation('customer', [
                'name' => new Text('name', true),
            ]),
            'entries'  => new Relation('entries', [
                'serial_number' => new Text('serial_number', true),
                'product'       => new Relation('product', [
                    'sku'  => new Text('sku', true),
                    'name' => new Text('name', true),
                ]),
            ]),
        ];
    }
    // </editor-fold>
}
