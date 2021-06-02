<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Level;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function mb_strlen;
use function str_pad;

class EloquentListener implements Subscriber {
    public function __construct(
        protected Logger $logger,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        /** @var array<string,array{level:\App\Services\Logger\Models\Enums\Level,countable:array<string>}> $events */
        $events = [
            'eloquent.created'      => [
                'level'     => Level::info(),
                'countable' => [
                    'models_created' => 1,
                ],
            ],
            'eloquent.updated'      => [
                'level'     => Level::info(),
                'countable' => [
                    'models_updated' => 1,
                ],
            ],
            'eloquent.restored'     => [
                'level'     => Level::info(),
                'countable' => [
                    'models_restored' => 1,
                ],
            ],
            'eloquent.deleted'      => [
                'level'     => Level::notice(),
                'countable' => [
                    'models_deleted' => 1,
                ],
            ],
            'eloquent.forceDeleted' => [
                'level'     => Level::warning(),
                'countable' => [
                    'models_force_deleted' => 1,
                ],
            ],
        ];

        foreach ($events as $event => $settings) {
            $dispatcher->listen("{$event}: *", function (Model $model) use ($event, $settings): void {
                $this->logger->event(
                    $settings['level'],
                    $event,
                    $model,
                    $this->getContext($model),
                    $settings['countable'],
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
