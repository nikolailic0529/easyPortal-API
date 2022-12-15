<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Events;

use App\Utils\Providers\EventsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use WeakMap;

use function reset;

/**
 * Laravel doesn't provide any way to remove the concrete listener, but it is
 * required e.g. for Processors while process huge amount of objects when
 * listeners should be reset between chunks. This class designed specially
 * to solve this problem, so you can relax and don't worry about unsubscribing.
 */
class Subject implements EventsProvider {
    /**
     * @var WeakMap<OnModelSaved, OnModelSaved>
     */
    private WeakMap $onSave;
    /**
     * @var WeakMap<OnModelDeleted, OnModelDeleted>
     */
    private WeakMap $onDelete;

    public function __construct() {
        $this->onSave   = new WeakMap();
        $this->onDelete = new WeakMap();
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            'eloquent.created: *',
            'eloquent.updated: *',
            'eloquent.deleted: *',
        ];
    }

    /**
     * @param array<mixed> $args
     */
    public function __invoke(string $event, array $args): void {
        $name  = Str::before($event, ':');
        $model = reset($args);

        if (!($model instanceof Model)) {
            return;
        }

        switch ($name) {
            case 'eloquent.created':
            case 'eloquent.updated':
                foreach ($this->onSave as $observer) {
                    $observer->modelSaved($model);
                }
                break;
            case 'eloquent.deleted':
                foreach ($this->onDelete as $observer) {
                    $observer->modelDeleted($model);
                }
                break;
            default:
                // ignore
                break;
        }
    }

    public function onModelEvent(object $observer): static {
        if ($observer instanceof OnModelSaved) {
            $this->onModelSaved($observer);
        }

        if ($observer instanceof OnModelDeleted) {
            $this->onModelDeleted($observer);
        }

        return $this;
    }

    public function onModelSaved(OnModelSaved $observer): static {
        $this->onSave[$observer] = $observer;

        return $this;
    }

    public function onModelDeleted(OnModelDeleted $observer): static {
        $this->onDelete[$observer] = $observer;

        return $this;
    }
}
