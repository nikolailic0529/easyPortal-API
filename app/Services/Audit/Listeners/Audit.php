<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Models\Model;
use App\Services\Audit\Auditor;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Audit\Events\QueryExported;
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
        $user = $event->user;
        if ($user instanceof Model) {
            $this->auditor->create(Action::authSignedIn(), ['guard' => $event->guard ]);
        }
    }

    public function signOut(Logout $event): void {
        $user = $event->user;
        if ($user instanceof Model) {
            $this->auditor->create(Action::authSignedOut(), ['guard' => $event->guard ]);
        }
    }

    /**
     * @param array<mixed> $args
     */
    public function modelEvent(string $event, array $args): void {
        $model = reset($args);
        if (!($model instanceof Model) || !($model instanceof Auditable)) {
            return;
        }
        $action  = $this->getModelAction($model, $event);
        $context = $this->getModelContext($model);
        $this->auditor->create($action, $context, $model);
    }

    public function queryExported(QueryExported $event): void {
        $this->auditor->create(Action::exported(), [
            'count'   => $event->getCount(),
            'type'    => $event->getType(),
            'columns' => $event->getColumns(),
        ]);
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, [$this::class, 'signIn']);
        $dispatcher->listen(Logout::class, [$this::class, 'signOut']);
        $dispatcher->listen(QueryExported::class, [$this::class, 'queryExported']);
        // Subscribe for model events
        /** @var array<string,\App\Services\Audit\Enums\Action> $events */
        $events = [
            'eloquent.created',
            'eloquent.updated',
            'eloquent.deleted',
        ];
        foreach ($events as $event) {
            $dispatcher->listen("{$event}: *", [$this::class, 'modelEvent']);
        }
    }

    protected function getModelAction(Model $model, string $event): Action {
        $class      = $model::class;
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
    protected function getModelContext(Model $model): array {
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
        ];
    }
}
