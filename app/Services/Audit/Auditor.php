<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\Audits\Audit as AuditModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Contexts\Context;
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
     * @param Context|array<string, mixed> $context
     */
    public function create(
        OrganizationProvider|Organization|string|null $org,
        Action $action,
        Model $model = null,
        Context|array $context = null,
        Authenticatable|string $user = null,
    ): void {
        // Org?
        if ($org instanceof OrganizationProvider) {
            $org = $org->defined() ? $org->get() : null;
        }

        if ($org instanceof Organization) {
            $org = $org->getKey();
        }

        // User?
        $user ??= $this->auth->getUser();

        if ($user instanceof Authenticatable) {
            if ($user instanceof User) {
                $user = $user->getKey();
            } else {
                throw new UnexpectedValueException(sprintf(
                    'The `$user` should be instance of `%s`, `%s` given.',
                    User::class,
                    $user::class,
                ));
            }
        }

        // Create
        $audit                  = new AuditModel();
        $audit->organization_id = $org;
        $audit->user_id         = $user;
        $audit->action          = $action;
        $audit->object_id       = $model?->getKey();
        $audit->object_type     = $model?->getMorphClass();
        $audit->context         = $context instanceof Context
            ? $context->jsonSerialize()
            : $context;

        $audit->save();
    }
}
