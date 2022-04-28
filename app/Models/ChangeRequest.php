<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasUser;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\PolymorphicModel;
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
 * @method static Builder|ChangeRequest newModelQuery()
 * @method static Builder|ChangeRequest newQuery()
 * @method static Builder|ChangeRequest query()
 */
class ChangeRequest extends PolymorphicModel implements OwnedByOrganization, Auditable {
    use HasFactory;
    use OwnedByOrganizationImpl;
    use HasFiles;
    use HasOrganization;
    use HasUser;

    protected const CASTS = [
        'cc'  => 'array',
        'bcc' => 'array',
        'to'  => 'array',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'change_requests';

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
     * @var array<string>
     */
    protected $visible = [
        'organization_id',
        'user_id',
        'object_id',
        'object_type',
        'subject',
        'from',
        'to',
        'cc',
        'bcc',
        'message',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
