<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\BelongsToTenant as BelongsToTenantContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use LogicException;

/**
 * @property int                          $id
 * @property string                       $organization_id
 * @property string|null                  $sub Auth0 User ID
 * @property bool                         $blocked
 * @property string                       $given_name
 * @property string                       $family_name
 * @property string                       $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string                       $phone
 * @property \Carbon\CarbonImmutable|null $phone_verified_at
 * @property string|null                  $photo
 * @property mixed                        $permissions
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereBlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereFamilyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereGivenName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, BelongsToTenantContract {
    use HasFactory;
    use Authenticatable;
    use Authorizable;
    use MustVerifyEmail;
    use BelongsToTenant;

    protected const CASTS = [
        'blocked'           => 'bool',
        'permissions'       => 'array',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $fillable = [
        'sub',
        'name',
        'email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $hidden = [
        // Not used, just for case.
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    public function sendEmailVerificationNotification(): void {
        throw new LogicException('Email verification should be done inside auth0.');
    }

    public function getAuthIdentifierName(): string {
        return 'sub';
    }
}
