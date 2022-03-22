<?php declare(strict_types = 1);

namespace App\Models\Audits;

use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Carbon\CarbonImmutable;
use Database\Factories\Audits\AuditFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit.
 *
 * @property string                 $id
 * @property Action                 $action
 * @property string|null            $user_id
 * @property string|null            $organization_id
 * @property string                 $object_id
 * @property string                 $object_type
 * @property array|null             $context
 * @property CarbonImmutable        $created_at
 * @property CarbonImmutable        $updated_at
 * @property-read Organization|null $organization
 * @property-read User|null         $user
 * @method static AuditFactory factory(...$parameters)
 * @method static Builder|Audit newModelQuery()
 * @method static Builder|Audit newQuery()
 * @method static Builder|Audit query()
 * @mixin Eloquent
 */
class Audit extends Model implements OwnedByOrganization {
    use HasFactory;
    use OwnedByOrganizationImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'audits';

    protected const CASTS = [
        'context' => 'json',
        'action'  => Action::class,
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    #[CascadeDelete(false)]
    public function user(): BelongsTo {
        // Relation between 2 table on 2 different db
        return $this->setConnection((new User())->getConnectionName())->belongsTo(User::class);
    }

    #[CascadeDelete(false)]
    public function organization(): BelongsTo {
        // Relation between 2 table on 2 different db
        return $this->setConnection((new Organization())->getConnectionName())->belongsTo(Organization::class);
    }
}
