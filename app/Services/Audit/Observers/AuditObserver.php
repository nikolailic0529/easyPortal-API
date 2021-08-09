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
            $this->audit->create(Action::modelCreated(), $model);
        }
    }

    public function updated(Model $model): void {
        if ($model instanceof Auditable) {
            $this->audit->create(Action::modelUpdated(), $model);
        }
    }

    public function deleted(Model $model): void {
        if ($model instanceof Auditable) {
            $this->audit->create(Action::modelDeleted(), $model);
        }
    }

}
