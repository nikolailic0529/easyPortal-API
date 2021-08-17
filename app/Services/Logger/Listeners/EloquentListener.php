<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function reset;
use function str_replace;

class EloquentListener extends Listener {
    use Database;

    public function subscribe(Dispatcher $dispatcher): void {
        /** @var array<string,string> $events */
        $events  = [
            'eloquent.created'      => 'created',
            'eloquent.updated'      => 'updated',
            'eloquent.restored'     => 'restored',
            'eloquent.deleted'      => 'softDeleted',
            'eloquent.forceDeleted' => 'forceDeleted',
        ];
        $changes = $this->config->get('ep.logger.eloquent.models');

        foreach ($events as $event => $property) {
            $dispatcher->listen(
                "{$event}: *",
                $this->getSafeListener(function (string $name, array $args) use ($changes, $event, $property): void {
                    // Should be ignored?
                    $model = reset($args);

                    if (!($model instanceof Model) || $this->isConnectionIgnored($model->getConnection())) {
                        return;
                    }

                    // Log
                    $object    = new EloquentObject($model);
                    $action    = $this->getAction($object, $event);
                    $countable = [
                        "{$this->getCategory()}.total.models.models"                     => 1,
                        "{$this->getCategory()}.total.models.{$property}"                => 1,
                        "{$this->getCategory()}.models.{$object->getType()}.{$property}" => 1,
                    ];

                    if ($changes) {
                        $this->logger->event(
                            $this->getCategory(),
                            $action,
                            Status::success(),
                            $object,
                            $this->getContext($object, $action),
                            $countable,
                        );
                    } else {
                        $this->logger->count($countable);
                    }
                }),
            );
        }
    }

    protected function getAction(EloquentObject $object, string $event): string {
        $action = str_replace('eloquent.', 'model.', $event);

        if ($action === 'model.deleted') {
            if ($object->isSoftDeletable()) {
                $action = 'model.softDeleted';
            } else {
                $action = 'model.forceDeleted';
            }
        }

        return $action;
    }

    /**
     * @return array<string,array{value:mixed,previous:mixed}>|null
     */
    protected function getContext(EloquentObject $object, string $action): ?array {
        $context = [];

        if ($action === 'model.created') {
            $context = [
                'properties' => $object->getProperties(),
            ];
        } else {
            $context = [
                'properties' => $object->getChanges(),
            ];
        }

        if (!array_filter($context)) {
            $context = null;
        }

        return $context;
    }

    protected function getCategory(): Category {
        return Category::eloquent();
    }
}
