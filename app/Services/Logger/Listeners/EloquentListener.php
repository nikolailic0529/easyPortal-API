<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function array_filter;
use function array_intersect_key;
use function class_uses_recursive;
use function in_array;
use function mb_strlen;
use function reset;
use function str_pad;
use function str_replace;

class EloquentListener extends Listener {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool>
     */
    private array $softDeletable = [];

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
                    $model     = reset($args);
                    $object    = new EloquentObject($model);
                    $action    = $this->getAction($model, $event);
                    $countable = [
                        "{$this->getCategory()}.total.models.models"                     => 1,
                        "{$this->getCategory()}.total.models.{$property}"                => 1,
                        "{$this->getCategory()}.models.{$object->getType()}.{$property}" => 1,
                    ];

                    if ($changes) {
                        $this->logger->event(
                            $this->getCategory(),
                            $action,
                            $object,
                            $this->getContext($model),
                            $countable,
                        );
                    } else {
                        $this->logger->count($countable);
                    }
                }),
            );
        }
    }

    protected function getAction(Model $model, string $event): string {
        $action = str_replace('eloquent.', 'model.', $event);

        if ($action === 'model.deleted') {
            if ($this->isSoftDeletable($model)) {
                $action = 'model.softDeleted';
            } else {
                $action = 'model.forceDeleted';
            }
        }

        return $action;
    }

    /**
     * @return array<mixed>|null
     */
    protected function getContext(Model $model): ?array {
        $context = [];

        if ($model->wasRecentlyCreated) {
            $context = [
                'properties' => $model->getAttributes(),
            ];
        } else {
            $changes = $model->getChanges();
            $context = [
                'changes'   => $this->hideProperties($model, $changes),
                'originals' => $this->hideProperties($model, array_intersect_key($model->getRawOriginal(), $changes)),
            ];
        }

        if (!array_filter($context)) {
            $context = null;
        }

        return $context;
    }

    /**
     * @param array<string,mixed> $attributes
     *
     * @return array<string,mixed>
     */
    protected function hideProperties(Model $model, array $attributes = []): array {
        $hidden = $model->getHidden();

        foreach ($hidden as $attribute) {
            if (isset($attributes[$attribute])) {
                $attributes[$attribute] = str_pad('', mb_strlen((string) $attributes[$attribute]), '*');
            }
        }

        return $attributes;
    }

    protected function isSoftDeletable(Model $model): bool {
        if (!isset($this->softDeletable[$model::class])) {
            $this->softDeletable[$model::class] = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        }

        return $this->softDeletable[$model::class];
    }

    protected function getCategory(): Category {
        return Category::eloquent();
    }
}
