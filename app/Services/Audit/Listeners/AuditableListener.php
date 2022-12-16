<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Logger\Listeners\EloquentObject;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

use function array_filter;
use function reset;

class AuditableListener extends Listener {
    public const PROPERTIES = 'properties';
    public const RELATIONS  = 'relations';

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            'eloquent.created: *',
            'eloquent.updated: *',
            'eloquent.deleted: *',
            'eloquent.restored: *',
        ];
    }

    /**
     * @param array<mixed> $args
     */
    public function __invoke(string $event, array $args): void {
        // Auditable?
        $model = reset($args);

        if (!($model instanceof Model) || !($model instanceof Auditable)) {
            return;
        }

        // Changed?
        $object = new EloquentObject($model);
        $action = $this->getModelAction($event);

        if ($action === Action::modelUpdated() && !$this->isModelChanged($model)) {
            return;
        }

        // Record
        $this->auditor->create($this->org, $action, $model, $this->getModelContext($action, $object));
    }

    protected function getModelAction(string $event): Action {
        $name   = Str::before($event, ':');
        $action = match ($name) {
            'eloquent.created'  => Action::modelCreated(),
            'eloquent.updated'  => Action::modelUpdated(),
            'eloquent.deleted'  => Action::modelDeleted(),
            'eloquent.restored' => Action::modelRestored(),
            default             => throw new LogicException("Event `{$event}` is unknown."),
        };

        return $action;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getModelContext(Action $action, EloquentObject $object): array {
        $model   = $object->getModel();
        $context = array_filter([
            self::PROPERTIES => $action === Action::modelCreated()
                ? $object->getProperties()
                : $object->getChanges(),
            self::RELATIONS  => $model instanceof Auditable
                ? $model->getDirtyRelations()
                : [],
        ]);

        return $context;
    }

    // todo(audit): Method the same as Data::isModelChanged()
    protected function isModelChanged(Model $model): bool {
        // Created or Deleted?
        if ($model->wasRecentlyCreated || !$model->exists) {
            return true;
        }

        // Relations?
        if ($model instanceof Auditable && !!$model->getDirtyRelations()) {
            return true;
        }

        // Dirty?
        $dirty = $model->getDirty();

        unset($dirty[$model->getUpdatedAtColumn()]);
        unset($dirty['synced_at']);

        return (bool) $dirty;
    }
}
