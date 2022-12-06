<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasFiles;
use App\Models\Relations\HasObject;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasUser;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Traits\AuditableImpl;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\ChangeRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Change Request.
 *
 * @property string               $id
 * @property string               $organization_id
 * @property string               $user_id
 * @property string               $object_id
 * @property string               $object_type
 * @property string               $subject
 * @property string               $from
 * @property array<string>        $to
 * @property array<string>|null   $cc
 * @property array<string>|null   $bcc
 * @property string               $message
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property Organization         $organization
 * @property User                 $user
 * @property Collection<int,File> $files
 * @method static ChangeRequestFactory factory(...$parameters)
 * @method static Builder<ChangeRequest>|ChangeRequest newModelQuery()
 * @method static Builder<ChangeRequest>|ChangeRequest newQuery()
 * @method static Builder<ChangeRequest>|ChangeRequest query()
 */
class ChangeRequest extends Model implements OwnedByOrganization, Auditable {
    use HasFactory;
    use AuditableImpl;
    use OwnedByOrganizationImpl;
    use HasFiles;
    use HasOrganization;
    use HasUser;
    use HasObject;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'change_requests';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'cc'  => 'array',
        'bcc' => 'array',
        'to'  => 'array',
    ];
}
