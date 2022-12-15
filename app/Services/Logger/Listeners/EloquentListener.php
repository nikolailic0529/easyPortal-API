<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function array_filter;
use function config;
use function reset;
use function str_replace;

class EloquentListener extends Listener {
    use Database;

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            'eloquent.created: *',
            'eloquent.updated: *',
            'eloquent.restored: *',
            'eloquent.deleted: *',
            'eloquent.forceDeleted: *',
        ];
    }

    /**
     * @param array<mixed> $args
     */
    public function __invoke(string $event, array $args): void {
        $this->call(function () use ($event, $args): void {
            // Should be ignored?
            $model = reset($args);

            if (!($model instanceof Model) || $this->isConnectionIgnored($model->getConnection())) {
                return;
            }

            // Property?
            // todo(Logger): Do we need to check that Model is soft deletable?
            //      In this case `eloquent.deleted` probably should not be renamed.
            $name     = Str::after(Str::before($event, ':'), '.');
            $property = match ($name) {
                'deleted'      => 'softDeleted',
                'forceDeleted' => 'forceDeleted',
                default        => $name,
            };

            // Log
            $object    = new EloquentObject($model);
            $action    = $this->getAction($object, $event);
            $changes   = config('ep.logger.eloquent.models');
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
        });
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
