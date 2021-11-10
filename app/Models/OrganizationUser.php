<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\Audit\Concerns\Auditable;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Organization User (pivot)
 *
 * @property string                        $id
 * @property string                        $organization_id
 * @property string                        $user_id
 * @property string|null                   $role_id
 * @property string|null                   $team_id
 * @property bool                          $enabled
 * @property \Carbon\CarbonImmutable       $created_at
 * @property \Carbon\CarbonImmutable       $updated_at
 * @property \Carbon\CarbonImmutable|null  $deleted_at
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\Role|null    $role
 * @property-read \App\Models\Team|null    $team
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationUser query()
 * @mixin \Eloquent
 */
class OrganizationUser extends Model implements Auditable {
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organization_users';

    protected const CASTS = [
        'enabled' => 'bool',
    ] + parent::CASTS;

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }
}
