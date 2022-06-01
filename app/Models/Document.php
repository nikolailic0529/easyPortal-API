<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCurrency;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasLanguage;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasResellerNullable;
use App\Models\Relations\HasServiceGroup;
use App\Models\Relations\HasStatuses;
use App\Models\Relations\HasType;
use App\Models\Scopes\DocumentStatusScope;
use App\Models\Scopes\DocumentStatusScopeImpl;
use App\Models\Scopes\DocumentTypeContractScope;
use App\Models\Scopes\DocumentTypeQueries;
use App\Models\Scopes\DocumentTypeQuoteType;
use App\Models\Scopes\DocumentTypeScopeImpl;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Date;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
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
use Illuminate\Support\Collection as BaseCollection;

use function app;
use function count;

/**
 * Document.
 *
 * @property string                         $id
 * @property string                         $oem_id
 * @property string|null                    $oem_said
 * @property string|null                    $oem_group_id
 * @property string                         $type_id
 * @property string|null                    $customer_id
 * @property string|null                    $reseller_id
 * @property string|null                    $distributor_id
 * @property string                         $number
 * @property CarbonImmutable|null           $start
 * @property CarbonImmutable|null           $end
 * @property string|null                    $price
 * @property string|null                    $currency_id
 * @property string|null                    $language_id
 * @property int                            $assets_count
 * @property int                            $entries_count
 * @property int                            $contacts_count
 * @property int                            $statuses_count
 * @property CarbonImmutable|null           $changed_at
 * @property CarbonImmutable                $synced_at
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
 * @property Oem                            $oem
 * @property OemGroup|null                  $oemGroup
 * @property Reseller|null                  $reseller
 * @property Collection<int, Status>        $statuses
 * @property Type                           $type
 * @method static DocumentFactory factory(...$parameters)
 * @method static Builder|Document newModelQuery()
 * @method static Builder|Document newQuery()
 * @method static Builder|Document query()
 */
class Document extends Model implements OwnedByOrganization, Searchable {
    use HasFactory;
    use SearchableImpl;
    use OwnedByResellerImpl;
    use HasOem;
    use HasType;
    use HasStatuses;
    use HasServiceGroup;
    use HasResellerNullable;
    use HasCustomerNullable;
    use HasCurrency;
    use HasLanguage;
    use HasContacts;
    use SyncHasMany;
    use DocumentTypeScopeImpl;
    use DocumentStatusScopeImpl;

    /**
     * @use DocumentTypeQueries<static>
     */
    use DocumentTypeQueries;

    protected const CASTS = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
        'price'      => 'decimal:2',
        'start'      => 'date',
        'end'        => 'date',
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
    protected $table = 'documents';

    // <editor-fold desc="Relations">
    // =========================================================================
    #[CascadeDelete(true)]
    public function entries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }

    /**
     * @param BaseCollection<int, DocumentEntry>|array<DocumentEntry> $entries
     */
    public function setEntriesAttribute(BaseCollection|array $entries): void {
        $this->syncHasMany('entries', $entries);
        $this->entries_count = count($entries);
        $this->assets_count  = (new BaseCollection($entries))
            ->map(static function (DocumentEntry $entry): string {
                return $entry->asset_id;
            })
            ->unique()
            ->count();
    }

    #[CascadeDelete(false)]
    public function distributor(): BelongsTo {
        return $this->belongsTo(Distributor::class);
    }

    public function setDistributorAttribute(?Distributor $distributor): void {
        $this->distributor()->associate($distributor);
    }

    #[CascadeDelete(false)]
    public function oemGroup(): BelongsTo {
        return $this->belongsTo(OemGroup::class);
    }

    public function setOemGroupAttribute(?OemGroup $group): void {
        $this->oemGroup()->associate($group);
    }

    #[CascadeDelete(true)]
    public function notes(): HasMany {
        return $this->hasMany(Note::class);
    }

    #[CascadeDelete(false)]
    public function assets(): HasManyThrough {
        return $this->hasManyThrough(
            Asset::class,
            DocumentEntry::class,
            null,
            (new Asset())->getKeyName(),
            null,
            'asset_id',
        );
    }

    protected function getStatusesPivot(): Pivot {
        return new DocumentStatus();
    }
    // </editor-fold>

    // <editor-fold desc="Attributes">
    // =========================================================================
    public function getIsContractAttribute(): bool {
        return app()->make(DocumentTypeContractScope::class)->isContractType($this->type_id);
    }

    public function getIsQuoteAttribute(): bool {
        return app()->make(DocumentTypeQuoteType::class)->isQuoteType($this->type_id);
    }

    public function getIsHiddenAttribute(): bool {
        return app()->make(DocumentStatusScope::class)->isHidden($this->statuses);
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
