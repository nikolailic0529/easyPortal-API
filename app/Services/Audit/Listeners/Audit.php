<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Http\Controllers\Export\Events\QueryExported;
use App\Services\Audit\Auditor;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Logger\Listeners\EloquentObject;
use App\Utils\Eloquent\Model;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;

use function reset;
use function str_replace;

class Audit implements Subscriber {
    public function __construct(
        protected Auditor $auditor,
    ) {
        // empty
    }

    public function signIn(Login $event): void {
        $this->auditor->create(Action::authSignedIn(), ['guard' => $event->guard]);
    }

    public function signOut(Logout $event): void {
        $this->auditor->create(Action::authSignedOut(), ['guard' => $event->guard]);
    }

    public function passwordReset(PasswordReset $event): void {
        $this->auditor->create(Action::authPasswordReset(), null, null, $event->user);
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

        $this->auditor->create($action, $context, $object->getModel());
    }

    public function queryExported(QueryExported $event): void {
        $this->auditor->create(Action::exported(), [
            'type'  => $event->getType(),
            'query' => $event->getQuery(),
        ]);
    }

    public function failed(Failed $event): void {
        $this->auditor->create(Action::authFailed(), ['guard' => $event->guard]);
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, [$this::class, 'signIn']);
        $dispatcher->listen(Logout::class, [$this::class, 'signOut']);
        $dispatcher->listen(Failed::class, [$this::class, 'failed']);
        $dispatcher->listen(PasswordReset::class, [$this::class, 'passwordReset']);
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
