<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Client\Events\RequestEvent;
use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\LoggerObject;
use Illuminate\Support\Arr;

use function count;
use function explode;
use function is_array;
use function is_null;
use function mb_strtolower;
use function str_contains;
use function str_starts_with;
use function trim;

class DataLoaderObject implements LoggerObject {
    public function __construct(
        protected RequestEvent $event,
    ) {
        // empty
    }

    public function getId(): ?string {
        return $this->event instanceof RequestStarted
            ? ($this->event->getParams()['id'] ?? null)
            : null;
    }

    public function getType(): string {
        $type = $this->event->getSelector();

        if (str_contains($type, '.')) {
            $type = explode('.', $this->event->getSelector(), 2)[1];
        }

        return $type;
    }

    public function getCount(): int {
        $count = 0;

        if ($this->event instanceof RequestSuccessful || $this->event instanceof RequestFailed) {
            $result = Arr::get($this->event->getResponse(), $this->event->getSelector());
            $count  = is_array($result) ? count($result) : (int) ($result !== null);
        }

        return $count;
    }

    public function isMutation(): bool {
        return str_starts_with(mb_strtolower(trim($this->event->getQuery())), 'mutation ');
    }
}
