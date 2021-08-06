<?php declare(strict_types = 1);

namespace App\Services\Audit\Observers;

use App\Models\Model;
use App\Services\Audit\Auditor;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Audit\Enums\Action;

class AuditObserver {
    public function __construct(
        protected Auditor $audit,
    ) {
        // empty
    }

    public function created(Model $model): void {
        if ($model instanceof Auditable) {
            $this->audit->create(Action::created(), $model, null, $model->getAttributes());
        }
    }

    public function updated(Model $model): void {
        if ($model instanceof Auditable) {
            $old = [];
            foreach ($model->getChanges() as $field => $value) {
                $old[$field] = $model->getOriginal($field);
            }
            $this->audit->create(Action::updated(), $model, $old, $model->getChanges());
        }
    }

    public function deleted(Model $model): void {
        if ($model instanceof Auditable) {
            $this->audit->create(Action::deleted(), $model);
        }
    }
}
