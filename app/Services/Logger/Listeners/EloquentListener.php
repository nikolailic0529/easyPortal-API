<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function array_intersect_key;
use function mb_strlen;
use function reset;
use function str_pad;
use function str_replace;

class EloquentListener implements Subscriber {
    public function __construct(
        protected Logger $logger,
    ) {
        // empty
    }

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
            $dispatcher->listen("{$event}: *", function (string $name, array $args) use ($event, $countable): void {
                $model = reset($args);

                $this->logger->event(
                    Category::eloquent(),
                    str_replace('eloquent.', 'model.', $event),
                    $model,
                    $this->getContext($model),
                    $countable,
                );
            });
        }
    }

    /**
     * @return array<mixed>|null
     */
    protected function getContext(Model $model): ?array {
        $changes = $model->getChanges();
        $context = [
            'changes'   => $this->hideProperties($model, $changes),
            'originals' => $this->hideProperties($model, array_intersect_key($model->getRawOriginal(), $changes)),
        ];

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
}
