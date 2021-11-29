<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasFiles;
use App\Models\Relations\HasOrganization;
use App\Models\Relations\HasUser;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\PolymorphicModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Change Request.
 *
 * @property string                                                     $id
 * @property string                                                     $organization_id
 * @property string                                                     $user_id
 * @property string                                                     $object_id
 * @property string                                                     $object_type
 * @property string                                                     $subject
 * @property string                                                     $from
 * @property array<string>                                              $to
 * @property array<string>|null                                         $cc
 * @property array<string>|null                                         $bcc
 * @property string                                                     $message
 * @property \Carbon\CarbonImmutable                                    $created_at
 * @property \Carbon\CarbonImmutable                                    $updated_at
 * @property \Carbon\CarbonImmutable|null                               $deleted_at
 * @property \App\Models\Organization                                   $organization
 * @property \App\Models\User                                           $user
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\File> $files
 * @method static \Database\Factories\ChangeRequestFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChangeRequest query()
 * @mixin \Eloquent
 */
class ChangeRequest extends PolymorphicModel implements Auditable {
    use HasFactory;
    use OwnedByOrganization;
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
