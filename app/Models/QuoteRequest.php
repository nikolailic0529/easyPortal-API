<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCustomer;
use App\Models\Concerns\HasFiles;
use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasStatuses;
use App\Models\Concerns\HasType;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * QuoteRequest.
 *
 * @property string                                                       $id
 * @property string                                                       $oem_id
 * @property string                                                       $organization_id
 * @property string                                                       $user_id
 * @property string                                                       $customer_id
 * @property string                                                       $contact_id
 * @property string                                                       $message
 * @property \Carbon\CarbonImmutable                                      $created_at
 * @property \Carbon\CarbonImmutable                                      $updated_at
 * @property \Carbon\CarbonImmutable|null                                 $deleted_at
 * @property \App\Models\Oem                                              $oem
 * @property \App\Models\Organization                                     $organization
 * @property \App\Models\Customer                                         $customer
 * @property \App\Models\Contact                                          $contact
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status> $statuses
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\File>   $files
 * @method static \Database\Factories\QuoteRequestFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequest query()
 * @mixin \Eloquent
 */
class QuoteRequest extends Model {
    use HasFactory;
    use OwnedByOrganization;
    use HasOem;
    use HasCustomer;
    use HasStatuses;
    use HasType;
    use HasFiles;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_requests';

    protected function getStatusesPivot(): Pivot {
        return new QuoteRequestStatus();
    }

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('organization_id');
    }

    public function assets(): BelongsToMany {
        $pivot = new QuoteRequestAsset();

        return $this
            ->belongsToMany(Asset::class, $pivot->getTable(), 'request_id')
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withPivot(['duration_id', 'service_level_id'])
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection|array<string, mixed> $assetsWithPivot
     */
    public function setAssetsAttribute(Collection|array $assetsWithPivot): void {
        $this->assets()->sync($assetsWithPivot);
    }

    public function contact(): BelongsTo {
        return $this->belongsTo(Contact::class);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
}
