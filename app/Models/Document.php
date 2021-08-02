<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\CascadeDeletes\CascadeDeletable;
use App\Models\Concerns\Relations\HasContacts;
use App\Models\Concerns\Relations\HasCurrency;
use App\Models\Concerns\Relations\HasCustomer;
use App\Models\Concerns\Relations\HasLanguage;
use App\Models\Concerns\Relations\HasOem;
use App\Models\Concerns\Relations\HasReseller;
use App\Models\Concerns\Relations\HasServiceGroup;
use App\Models\Concerns\Relations\HasType;
use App\Models\Concerns\SyncHasMany;
use App\Models\Scopes\ContractTypeScope;
use App\Services\Organization\Eloquent\OwnedByReseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

use function count;

/**
 * Document.
 *
 * @property string                                                              $id
 * @property string                                                              $oem_id
 * @property string|null                                                         $oem_said
 * @property string|null                                                         $oem_group_id
 * @property string                                                              $type_id
 * @property string                                                              $customer_id
 * @property string|null                                                         $reseller_id
 * @property string|null                                                         $distributor_id
 * @property string                                                              $number
 * @property string|null                                                         $service_group_id
 * @property \Carbon\CarbonImmutable|null                                        $start
 * @property \Carbon\CarbonImmutable|null                                        $end
 * @property string|null                                                         $price
 * @property string|null                                                         $currency_id
 * @property string|null                                                         $language_id
 * @property int                                                                 $assets_count
 * @property int                                                                 $entries_count
 * @property int                                                                 $contacts_count
 * @property \Carbon\CarbonImmutable|null                                        $changed_at
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>       $contacts
 * @property \App\Models\Currency|null                                           $currency
 * @property \App\Models\Customer                                                $customer
 * @property \App\Models\Distributor|null                                        $distributor
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\DocumentEntry> $entries
 * @property \App\Models\Language|null                                           $language
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Note>     $notes
 * @property \App\Models\Oem                                                     $oem
 * @property \App\Models\OemGroup|null                                           $oemGroup
 * @property \App\Models\Reseller|null                                           $reseller
 * @property \App\Models\ServiceGroup|null                                       $serviceGroup
 * @property \App\Models\Type                                                    $type
 * @method static \Database\Factories\DocumentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document queryContracts()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Document query()
 * @mixin \Eloquent
 */
class Document extends Model implements CascadeDeletable {
    use OwnedByReseller;
    use HasFactory;
    use HasOem;
    use HasType;
    use HasServiceGroup;
    use HasReseller;
    use HasCustomer;
    use HasCurrency;
    use HasLanguage;
    use HasContacts;
    use SyncHasMany;
    use ContractTypeScope;

    protected const CASTS = [
        'changed_at' => 'datetime',
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

    public function isCascadeDeletableRelation(string $name, Relation $relation, bool $default): bool {
        return $name === 'entries';
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
}
