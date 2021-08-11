<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\Audits\Audit as AuditModel;
use App\Models\Model;
use App\Services\Audit\Enums\Action;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;

class Auditor {
    public const CONNECTION = 'audit';

    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $context
     */
    public function create(Action $action, array $context = null, Model $model = null): void {
        $organization = null;
        if ($this->organization->defined()) {
            $organization = $this->organization->getKey();
        }
        $user = $this->auth->user();
        // create audit
        $audit                  = new AuditModel();
        $audit->action          = $action;
        $audit->object_id       = $model ? $model->getKey() : null;
        $audit->object_type     = $model ? $model->getMorphClass() : null;
        $audit->user_id         = $user ? $user->getKey() : null;
        $audit->organization_id = $organization;
        $audit->context         = $context;
        $audit->save();
    }
}
