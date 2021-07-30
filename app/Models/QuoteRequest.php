<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCustomer;
use App\Models\Concerns\HasFiles;
use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasStatuses;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @property \App\Models\Customer                                         $customer
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
}
