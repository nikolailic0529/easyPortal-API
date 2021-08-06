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
     * @param array<string, mixed> $old_values
     *
     * @param array<string, mixed> $new_values
     *
     */
    public function create(
        Model $model,
        Action $action,
        array $old_values = null,
        array $new_values = null,
    ): void {
        $user = $this->auth->user();
        // create audit
        $audit                  = new AuditModel();
        $audit->action          = $action;
        $audit->object_id       = $model ? $model->getKey() : null;
        $audit->object_type     = $model ? $model->getMorphClass() : null;
        $audit->user_id         = $user ? $user->getKey() : null;
        $audit->organization_id = $this->organization->getKey();
        $audit->old_values      = $old_values;
        $audit->new_values      = $new_values;
        $audit->save();
    }
}
