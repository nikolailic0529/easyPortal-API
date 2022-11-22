<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Http\Controllers\Export\Events\QueryExported;
use App\Services\Audit\Auditor;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Logger\Listeners\EloquentObject;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Events\Dispatcher;

use function reset;
use function str_replace;

class Audit implements Subscriber {
    public function __construct(
        protected Auditor $auditor,
        protected CurrentOrganization $org,
    ) {
        // empty
    }

    /**
     * @param array<mixed> $args
     */
    public function modelEvent(string $event, array $args): void {
        $model = reset($args);
        if (!($model instanceof Model) || !($model instanceof Auditable)) {
            return;
        }

        $object = new EloquentObject($model);
        $action = $this->getModelAction($object, $event);

        if ($action === Action::modelUpdated() && !$this->isModelChanged($model)) {
            return;
        }

        $context = $this->getModelContext($object, $action);

        $this->auditor->create($this->org, $action, $model, $context);
    }

    public function queryExported(QueryExported $event): void {
        $this->auditor->create($this->org, Action::exported(), null, [
            'type'  => $event->getType(),
            'query' => $event->getQuery(),
        ]);
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(QueryExported::class, [$this::class, 'queryExported']);
        // Subscribe for model events
        /** @var array<string,Action> $events */
        $events = [
            'eloquent.created',
            'eloquent.updated',
            'eloquent.deleted',
            'eloquent.restored',
        ];
        foreach ($events as $event) {
            $dispatcher->listen("{$event}: *", [$this::class, 'modelEvent']);
        }
    }

    protected function getModelAction(EloquentObject $object, string $event): Action {
        $class      = $object->getModel()::class;
        $actionName = str_replace('eloquent.', '', $event);
        $actionName = str_replace(": {$class}", '', $actionName);
        $action     = null;

        switch ($actionName) {
            case 'created':
                $action = Action::modelCreated();
                break;
            case 'updated':
                $action = Action::modelUpdated();
                break;
            case 'deleted':
                $action = Action::modelDeleted();
                break;
            case 'restored':
                $action = Action::modelRestored();
                break;
            default:
                // empty
                break;
        }

        return $action;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getModelContext(EloquentObject $object, Action $action): array {
        $context = [];
        if ($action === Action::modelCreated()) {
            $context = [
                'properties' => $object->getProperties(),
            ];
        } else {
            $context = [
                'properties' => $object->getChanges(),
            ];
        }

        return $context;
    }

    // todo(audit): Method the same as Data::isModelChanged()
    protected function isModelChanged(Model $model): bool {
        // Created or Deleted?
        if ($model->wasRecentlyCreated || !$model->exists) {
            return true;
        }

        // Dirty?
        $dirty = $model->getDirty();

        unset($dirty[$model->getUpdatedAtColumn()]);
        unset($dirty['synced_at']);

        return (bool) $dirty;
    }
}
