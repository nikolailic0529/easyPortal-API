<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Http\Controllers\QueryExported;
use App\Models\Model;
use App\Services\Audit\Auditor;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Logger\Listeners\EloquentObject;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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
        $this->auditor->create(Action::authSignedIn(), ['guard' => $event->guard ]);
    }

    public function signOut(Logout $event): void {
        $this->auditor->create(Action::authSignedOut(), ['guard' => $event->guard ]);
    }

    /**
     * @param array<mixed> $args
     */
    public function modelEvent(string $event, array $args): void {
        $model = reset($args);
        if (!($model instanceof Model) || !($model instanceof Auditable)) {
            return;
        }
        $object  = new EloquentObject($model);
        $action  = $this->getModelAction($object, $event);
        $context = $this->getModelContext($object, $action);
        $this->auditor->create($action, $context, $object->getModel());
    }

    public function queryExported(QueryExported $event): void {
        $this->auditor->create(Action::exported(), [
            'count'   => $event->getCount(),
            'type'    => $event->getType(),
            'query'   => $event->getQuery(),
            'columns' => $event->getColumns(),
        ]);
    }

    public function failed(Failed $event): void {
        $this->auditor->create(Action::authFailed(), ['guard' => $event->guard]);
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, [$this::class, 'signIn']);
        $dispatcher->listen(Logout::class, [$this::class, 'signOut']);
        $dispatcher->listen(Failed::class, [$this::class, 'failed']);
        $dispatcher->listen(QueryExported::class, [$this::class, 'queryExported']);
        // Subscribe for model events
        /** @var array<string,\App\Services\Audit\Enums\Action> $events */
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
     * @param array<string, mixed> $extra
     *
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
}
