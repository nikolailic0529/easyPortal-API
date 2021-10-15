<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\CascadeDeletes\CascadeDeletable;
use App\Models\Concerns\Relations\HasContacts;
use App\Models\Concerns\Relations\HasCurrency;
use App\Models\Concerns\Relations\HasCustomerNullable;
use App\Models\Concerns\Relations\HasLanguage;
use App\Models\Concerns\Relations\HasOem;
use App\Models\Concerns\Relations\HasResellerNullable;
use App\Models\Concerns\Relations\HasServiceGroup;
use App\Models\Concerns\Relations\HasStatuses;
use App\Models\Concerns\Relations\HasType;
use App\Models\Concerns\SyncHasMany;
use App\Models\Scopes\ContractType;
use App\Models\Scopes\DocumentTypeQuery;
use App\Models\Scopes\DocumentTypeScope;
use App\Models\Scopes\QuoteType;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Date;
use App\Services\Search\Properties\Double;
use App\Services\Search\Properties\Text;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

use function app;
use function count;

/**
 * Document.
 *
 * @property string                                                              $id
 * @property string                                                              $oem_id
 * @property string|null                                                         $oem_said
 * @property string|null                                                         $oem_group_id
 * @property string                                                              $type_id
 * @property string|null                                                         $customer_id
 * @property string|null                                                         $reseller_id
 * @property string|null                                                         $distributor_id
 * @property string                                                              $number
 * @property \Carbon\CarbonImmutable|null                                        $start
 * @property \Carbon\CarbonImmutable|null                                        $end
 * @property string|null                                                         $price
 * @property string|null                                                         $currency_id
 * @property string|null                                                         $language_id
 * @property int                                                                 $assets_count
 * @property int                                                                 $entries_count
 * @property int                                                                 $contacts_count
 * @property \Carbon\CarbonImmutable|null                                        $changed_at
 * @property \Carbon\CarbonImmutable                                             $synced_at
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>       $contacts
 * @property \App\Models\Currency|null                                           $currency
 * @property \App\Models\Customer|null                                           $customer
 * @property \App\Models\Distributor|null                                        $distributor
 * @property-read bool                                                           $is_contract
 * @property-read bool                                                           $is_quote
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry> $entries
 * @property \App\Models\Language|null                                           $language
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Note>     $notes
 * @property \App\Models\Oem                                                     $oem
 * @property \App\Models\OemGroup|null                                           $oemGroup
 * @property \App\Models\Reseller|null                                           $reseller
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status>        $statuses
 * @property \App\Models\Type                                                    $type
 * @method static \Database\Factories\DocumentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document queryContracts()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document queryDocuments()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document queryQuotes()
 * @mixin \Eloquent
 */
class Document extends Model implements CascadeDeletable {
    use HasFactory;
    use Searchable;
    use OwnedByReseller;
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
    use DocumentTypeScope;
    use DocumentTypeQuery;

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
    public function entries(): HasMany {
        return $this->hasMany(DocumentEntry::class);
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\DocumentEntry>|array<\App\Models\DocumentEntry> $entries
     */
    public function setEntriesAttribute(Collection|array $entries): void {
        $this->syncHasMany('entries', $entries);
        $this->entries_count = count($entries);
        $this->assets_count  = (new Collection($entries))
            ->map(static function (DocumentEntry $entry): string {
                return $entry->asset_id;
            })
            ->unique()
            ->count();
    }

    public function distributor(): BelongsTo {
        return $this->belongsTo(Distributor::class);
    }

    public function setDistributorAttribute(?Distributor $distributor): void {
        $this->distributor()->associate($distributor);
    }

    public function oemGroup(): BelongsTo {
        return $this->belongsTo(OemGroup::class);
    }

    public function setOemGroupAttribute(?OemGroup $group): void {
        $this->oemGroup()->associate($group);
    }

    public function notes(): HasMany {
        return $this->hasMany(Note::class);
    }

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
        return app()->make(ContractType::class)->isContractType($this->type_id);
    }

    public function getIsQuoteAttribute(): bool {
        return app()->make(QuoteType::class)->isQuoteType($this->type_id);
    }
    // </editor-fold>

    // <editor-fold desc="CascadeDeletes">
    // =========================================================================
    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool {
        return $name === 'entries';
    }
    // </editor-fold>

    // <editor-fold desc="Searchable">
    // =========================================================================
    public function shouldBeSearchable(): bool {
        return $this->is_contract || $this->is_quote;
    }

    /**
     * @inheritDoc
     */
    protected static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'number'   => new Text('number', true),
            'start'    => new Date('start'),
            'end'      => new Date('end'),
            'price'    => new Double('price'),
            'customer' => [
                'name' => new Text('customer.name', true),
            ],
        ];
    }
    // </editor-fold>
}
