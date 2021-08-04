<?php declare(strict_types = 1);

namespace App\Observers;

use App\Models\Audits\Audit;
use App\Models\Concerns\Audits\Auditable;
use App\Models\Model;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Arr;

use function in_array;

class AuditObserver {
    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function created(Auditable $model): void {
        foreach ($model->getAttributes() as $field => $value) {
            if ($this->isAuditableAttribute($model, $field)) {
                $this->createAudit(
                    $model,
                    'create',
                    $field,
                    null,
                    $value,
                );
            }
        }
    }

    public function updated(Auditable $model): void {
        foreach ($model->getDirty() as $field => $value) {
            if ($this->isAuditableAttribute($model, $field)) {
                $this->createAudit(
                    $model,
                    'update',
                    $field,
                    Arr::get($model->getOriginal(), $field),
                    Arr::get($model->getAttributes(), $field),
                );
            }
        }
    }

    public function deleted(Auditable $model): void {
        $this->createAudit($model, 'delete');
    }

    protected function createAudit(
        Model $model,
        string $action,
        string $field = null,
        mixed $old_value = null,
        mixed $new_value = null,
    ): void {
        $user = $this->auth->user();
        // create audit
        $audit                  = new Audit();
        $audit->action          = $action;
        $audit->object_id       = $model->getKey();
        $audit->object_type     = $model->getMorphClass();
        $audit->user_id         = $user;
        $audit->organization_id = $this->organization->get()->getKey();
        $audit->field           = $field;
        $audit->old_value       = $old_value;
        $audit->new_value       = $new_value;
        $audit->save();
    }

    protected function isAuditableAttribute(Auditable $model, string $attribute): bool {
        return !in_array($attribute, $model->getAuditableExcludedAttributes(), true);
    }
}
