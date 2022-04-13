<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasType;
use App\Models\Relations\HasUser;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteRequestFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection as BaseCollection;

/**
 * QuoteRequest.
 *
 * @property string                             $id
 * @property string                             $oem_id
 * @property string                             $organization_id
 * @property string                             $user_id
 * @property string|null                        $customer_id
 * @property string|null                        $customer_name
 * @property string                             $type_id
 * @property string                             $message
 * @property CarbonImmutable                    $created_at
 * @property CarbonImmutable                    $updated_at
 * @property CarbonImmutable|null               $deleted_at
 * @property Oem                                $oem
 * @property Organization                       $organization
 * @property Customer|null                      $customer
 * @property Contact                            $contact
 * @property Type                               $type
 * @property Collection<int, Status>            $statuses
 * @property Collection<int, File>              $files
 * @property Collection<int, QuoteRequestAsset> $assets
 * @method static QuoteRequestFactory factory(...$parameters)
 * @method static Builder|QuoteRequest newModelQuery()
 * @method static Builder|QuoteRequest newQuery()
 * @method static Builder|QuoteRequest query()
 * @mixin Eloquent
 */
class QuoteRequest extends Model implements OwnedByOrganization, Auditable {
    use HasFactory;
    use OwnedByOrganizationImpl;
    use HasOem;
    use HasCustomerNullable;
    use HasType;
    use HasFiles;
    use HasUser;
    use SyncHasMany;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_requests';

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('organization_id');
    }

    #[CascadeDelete(true)]
    public function assets(): HasMany {
        return $this->hasMany(QuoteRequestAsset::class, 'request_id');
    }

    /**
     * @param BaseCollection|array<QuoteRequestAsset> $assets
     */
    public function setAssetsAttribute(BaseCollection|array $assets): void {
        $this->syncHasMany('assets', $assets);
    }

    #[CascadeDelete(true)]
    public function contact(): MorphOne {
        return $this->morphOne(Contact::class, 'object');
    }

    public function setContactAttribute(Contact $contact): void {
        $this->contact()->save($contact);
    }

    #[CascadeDelete(false)]
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
}
