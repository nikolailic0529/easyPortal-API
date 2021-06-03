<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function mb_strlen;
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
            $dispatcher->listen("{$event}: *", function (Model $model) use ($event, $countable): void {
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
     * @return array<mixed>
     */
    protected function getContext(Model $model): array {
        $context = [
            'changes'   => $this->hideProperties($model, $model->getChanges()),
            'originals' => $this->hideProperties($model, $model->getRawOriginal()),
        ];

        if (!array_filter($context)) {
            $context = [];
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
