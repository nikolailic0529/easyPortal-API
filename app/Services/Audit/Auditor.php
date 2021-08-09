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
     * @param array<string, mixed> $extra
     */
    public function create(
        Action $action,
        Model $model = null,
        array $extra = null,
    ): void {
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
        $audit->context         = $this->getContext($model, $extra);
        $audit->save();
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    protected function getContext(Model $model, ?array $extra): array {
        $properties = [];
        if ($model->wasRecentlyCreated) {
            // created
            foreach ($model->getAttributes() as $field => $value) {
                $properties[$field] = [
                    'value'    => $value,
                    'previous' => null,
                ];
            }
        } else {
            foreach ($model->getChanges() as $field => $value) {
                $properties[$field] = [
                    'value'    => $value,
                    'previous' => $model->getOriginal($field),
                ];
            }
        }
        return [
            'properties' => $properties,
            'extra'      =>  $extra,
        ];
    }
}
