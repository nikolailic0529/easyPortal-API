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
        /** @var array<string,array<string,int>> $events */
        $events = [
            'eloquent.created'      => [
                'models.created' => 1,
            ],
            'eloquent.updated'      => [
                'models.updated' => 1,
            ],
            'eloquent.restored'     => [
                'models.restored' => 1,
            ],
            'eloquent.deleted'      => [
                'models.deleted_soft' => 1,
            ],
            'eloquent.forceDeleted' => [
                'models.deleted_force' => 1,
            ],
        ];

        foreach ($events as $event => $countable) {
            $dispatcher->listen(
                "{$event}: *",
                $this->getSafeListener(function (string $name, array $args) use ($event, $countable): void {
                    $model  = reset($args);
                    $action = $this->getAction($model, $event);

                    $this->logger->event(
                        Category::eloquent(),
                        $action,
                        new EloquentObject($model),
                        $this->getContext($model),
                        $countable,
                    );
                }),
            );
        }
    }

    protected function getAction(Model $model, string $event): string {
        $action = str_replace('eloquent.', 'model.', $event);

        if ($action === 'model.deleted') {
            if ($this->isSoftDeletable($model)) {
                $action = 'model.soft-deleted';
            } else {
                $action = 'model.force-deleted';
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
}
