<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\Audits\Audit as AuditModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\Auth\Auth;
use App\Services\Organization\OrganizationProvider;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use UnexpectedValueException;

use function sprintf;

class Auditor {
    public const CONNECTION = 'audit';

    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $context
     */
    public function create(
        OrganizationProvider|Organization|null $org,
        Action $action,
        Model $model = null,
        array $context = null,
        Authenticatable $user = null,
    ): void {
        // Org?
        if ($org instanceof OrganizationProvider) {
            $org = $org->defined() ? $org->get() : null;
        }

        // User?
        $user ??= $this->auth->getUser();

        if ($user && !($user instanceof User)) {
            throw new UnexpectedValueException(sprintf(
                'The `$user` should be instance of `%s`, `%s` given.',
                User::class,
                $user::class,
            ));
        }

        // Create
        $audit                  = new AuditModel();
        $audit->action          = $action;
        $audit->object_id       = $model?->getKey();
        $audit->object_type     = $model?->getMorphClass();
        $audit->user_id         = $user?->getKey();
        $audit->organization_id = $org?->getKey();
        $audit->context         = $context;
        $audit->save();
    }
}
