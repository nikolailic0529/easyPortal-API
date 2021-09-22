<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\Organization\Eloquent\OwnedByOrganization;

/**
 * Organization User (pivot)
 *
 * @property string                       $id
 * @property string                       $organization_id
 * @property string                       $user_id
 * @property string|null                  $role_id
 * @property string|null                  $team_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser query()
 * @mixin \Eloquent
 */
class OrganizationUser extends Model {
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organization_users';
}
