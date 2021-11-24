<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\SyncHasMany;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOem;
use App\Models\Relations\HasType;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

/**
 * QuoteRequest.
 *
 * @property string                                                                        $id
 * @property string                                                                        $oem_id
 * @property string                                                                        $organization_id
 * @property string                                                                        $user_id
 * @property string|null                                                                   $customer_id
 * @property string|null                                                                   $customer_name
 * @property string                                                                        $type_id
 * @property string                                                                        $message
 * @property \Carbon\CarbonImmutable                                                       $created_at
 * @property \Carbon\CarbonImmutable                                                       $updated_at
 * @property \Carbon\CarbonImmutable|null                                                  $deleted_at
 * @property \App\Models\Oem                                                               $oem
 * @property \App\Models\Organization                                                      $organization
 * @property \App\Models\Customer|null                                                     $customer
 * @property \App\Models\Contact                                                           $contact
 * @property \App\Models\Type                                                              $type
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status>                  $statuses
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\File>                    $files
 * @property \Illuminate\Database\Eloquent\Collection|array<\App\Models\QuoteRequestAsset> $assets
 * @method static \Database\Factories\QuoteRequestFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest query()
 * @mixin \Eloquent
 */
class QuoteRequest extends Model implements Auditable {
    use HasFactory;
    use OwnedByOrganization;
    use HasOem;
    use HasCustomerNullable;
    use HasType;
    use HasFiles;
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

    public function assets(): HasMany {
        return $this->hasMany(QuoteRequestAsset::class, 'request_id');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\QuoteRequestAsset> $assets
     */
    public function setAssetsAttribute(Collection|array $assets): void {
        $this->syncHasMany('assets', $assets);
    }

    public function contact(): MorphOne {
        return $this->morphOne(Contact::class, 'object');
    }

    public function setContactAttribute(Contact $contact): void {
        $this->contact()->save($contact);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
}
