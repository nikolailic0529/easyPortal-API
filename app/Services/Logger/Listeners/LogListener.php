<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use Illuminate\Log\Events\MessageLogged;

class LogListener extends Listener {
    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            MessageLogged::class,
        ];
    }

    public function __invoke(MessageLogged $event): void {
        $this->call(function () use ($event): void {
            $this->logger->count([
                "{$this->getCategory()}.total.levels"           => 1,
                "{$this->getCategory()}.levels.{$event->level}" => 1,
            ]);
        });
    }

    protected function getCategory(): Category {
        return Category::log();
    }
}
