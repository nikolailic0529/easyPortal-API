<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Oem;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Relations\HasCustomerNullable;
use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOemNullable;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasTypeNullable;
use App\Models\Relations\HasUser;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * QuoteRequest.
 *
 * @property string                                $id
 * @property string                                $organization_id
 * @property string                                $user_id
 * @property bool                                  $user_copy
 * @property string|null                           $customer_id
 * @property string|null                           $customer_custom
 * @property string|null                           $oem_id
 * @property string|null                           $oem_custom
 * @property string|null                           $type_id
 * @property string|null                           $type_custom
 * @property string|null                           $message
 * @property CarbonImmutable                       $created_at
 * @property CarbonImmutable                       $updated_at
 * @property CarbonImmutable|null                  $deleted_at
 * @property Oem|null                              $oem
 * @property Organization                          $organization
 * @property Customer|null                         $customer
 * @property Contact                               $contact
 * @property Type|null                             $type
 * @property Collection<int, Status>               $statuses
 * @property Collection<int, File>                 $files
 * @property Collection<int, QuoteRequestAsset>    $assets
 * @property Collection<int, QuoteRequestDocument> $documents
 * @method static QuoteRequestFactory factory(...$parameters)
 * @method static Builder|QuoteRequest newModelQuery()
 * @method static Builder|QuoteRequest newQuery()
 * @method static Builder|QuoteRequest query()
 */
class QuoteRequest extends Model implements OwnedByOrganization, Auditable {
    use HasFactory;
    use OwnedByOrganizationImpl;
    use HasOemNullable;
    use HasCustomerNullable;
    use HasTypeNullable;
    use HasFiles;
    use HasUser;
    use HasOrganization;
    use SyncHasMany;

    protected const CASTS = [
        'user_copy' => 'bool',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_requests';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<mixed>
     */
    protected $casts = self::CASTS;

    public function getQualifiedOrganizationColumn(): string {
        return $this->qualifyColumn('organization_id');
    }

    #[CascadeDelete(true)]
    public function assets(): HasMany {
        return $this->hasMany(QuoteRequestAsset::class, 'request_id');
    }

    /**
     * @param Collection<int, QuoteRequestAsset> $assets
     */
    public function setAssetsAttribute(Collection $assets): void {
        $this->syncHasMany('assets', $assets);
    }

    /**
     * @return HasMany<QuoteRequestDocument>
     */
    #[CascadeDelete(true)]
    public function documents(): HasMany {
        return $this->hasMany(QuoteRequestDocument::class, 'request_id');
    }

    /**
     * @param Collection<int,QuoteRequestDocument> $documents
     */
    public function setDocumentsAttribute(Collection $documents): void {
        $this->syncHasMany('documents', $documents);
    }

    #[CascadeDelete(true)]
    public function contact(): MorphOne {
        return $this->morphOne(Contact::class, 'object');
    }

    public function setContactAttribute(Contact $contact): void {
        $this->setRelation('contact', $contact);
        $this->onSave(function () use ($contact): void {
            $this->contact()->save($contact);
        });
    }
}
