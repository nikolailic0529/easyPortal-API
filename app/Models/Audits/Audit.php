<?php declare(strict_types = 1);

namespace App\Models\Audits;

use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Utils\Eloquent\Model as AppModel;
use Carbon\CarbonImmutable;
use Database\Factories\Audits\AuditFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Audit.
 *
 * @property string                    $id
 * @property Action                    $action
 * @property string|null               $user_id
 * @property string|null               $organization_id
 * @property string|null               $object_id
 * @property string|null               $object_type
 * @property array<string, mixed>|null $context
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property AppModel|null             $object
 * @property-read Organization|null    $organization
 * @property-read User|null            $user
 * @method static AuditFactory factory(...$parameters)
 * @method static Builder<Audit> newModelQuery()
 * @method static Builder<Audit> newQuery()
 * @method static Builder<Audit> query()
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

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'context' => 'json',
        'action'  => Action::class,
    ];

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo {
        return $this->setConnection($this->getDefaultConnection())->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, self>
     */
    public function organization(): BelongsTo {
        return $this->setConnection($this->getDefaultConnection())->belongsTo(Organization::class);
    }

    /**
     * @return MorphTo<EloquentModel, self>
     */
    public function object(): MorphTo {
        return $this->setConnection($this->getDefaultConnection())->morphTo();
    }

    public function setObjectAttribute(?AppModel $object): void {
        $this->object_id   = $object?->getKey();
        $this->object_type = $object?->getMorphClass();

        $this->setRelation('object', $object);
    }

    protected function getDefaultConnection(): string {
        return DB::getDefaultConnection();
    }
}
