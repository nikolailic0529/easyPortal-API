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
use App\Models\Scopes\DocumentIsDocumentScopeImpl;
use App\Models\Scopes\DocumentIsHiddenScopeImpl;
use App\Models\Scopes\DocumentScopes;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Date;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Casts\Origin;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use function array_diff;
use function array_intersect;
use function config;
use function count;
use function in_array;

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
 * @property bool                           $is_hidden
 * @property bool                           $is_contract
 * @property bool                           $is_quote
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
 * @property-read bool                      $is_visible
 * @property-read bool                      $is_document
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
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasCurrency;
    use HasLanguage;
    use HasContacts;
    use HasChangeRequests;
    use SyncHasMany;
    use DocumentScopes;
    use DocumentIsDocumentScopeImpl;
    use DocumentIsHiddenScopeImpl;

    use HasTypeNullable {
        setTypeAttribute as private traitSetTypeAttribute;
    }

    use HasStatuses {
        setStatusesAttribute as private traitSetStatusesAttribute;
    }

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
        'is_contract'  => 'bool',
        'is_quote'     => 'bool',
        'is_hidden'    => 'bool',
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

    public function setTypeAttribute(?Type $type): void {
        $this->traitSetTypeAttribute($type);

        $this->is_quote    = self::isQuoteType($this->type_id);
        $this->is_contract = self::isContractType($this->type_id);
    }

    /**
     * @param Collection<int, Status> $statuses
     */
    public function setStatusesAttribute(Collection $statuses): void {
        $this->traitSetStatusesAttribute($statuses);

        $this->is_hidden = self::isHidden($this->statuses);
    }
    // </editor-fold>

    // <editor-fold desc="Attributes">
    // =========================================================================
    public function getIsVisibleAttribute(): bool {
        return $this->is_hidden === false;
    }

    public function getIsDocumentAttribute(): bool {
        return $this->is_contract || $this->is_quote;
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

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param Collection<array-key, Status>|Status|string $status
     */
    public static function isHidden(Collection|Status|string $status): bool {
        $hidden   = (array) config('ep.document_statuses_hidden');
        $statuses = [];

        if ($status instanceof Collection) {
            $statuses = $status->map(new GetKey())->all();
        } elseif ($status instanceof Status) {
            $statuses = [$status->getKey()];
        } else {
            $statuses = [$status];
        }

        return !!array_intersect($statuses, $hidden);
    }

    /**
     * @return array<string>
     */
    public static function getContractTypeIds(): array {
        return (array) config('ep.contract_types');
    }

    public static function isContractType(string|null $type): bool {
        return in_array($type, self::getContractTypeIds(), true);
    }

    /**
     * @return array<string>
     */
    public static function getQuoteTypeIds(): array {
        $quoteTypes    = (array) config('ep.quote_types');
        $contractTypes = self::getContractTypeIds();

        if ($contractTypes) {
            $quoteTypes = array_diff($quoteTypes, $contractTypes);
        }

        return $quoteTypes;
    }

    public static function isQuoteType(string|null $type): bool {
        $contractTypes = self::getContractTypeIds();
        $quoteTypes    = self::getQuoteTypeIds();
        $is            = false;

        if ($quoteTypes) {
            $is = in_array($type, $quoteTypes, true);
        } elseif ($contractTypes) {
            $is = !in_array($type, $contractTypes, true);
        } else {
            // empty
        }

        return $is;
    }
    // </editor-fold>
}
