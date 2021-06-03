<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;

class LogListener implements Subscriber {
    public function __construct(
        protected Logger $logger,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->log($event);
        });
    }

    protected function log(MessageLogged $event): void {
        $this->logger->count([
            "logs.{$event->level}" => 1,
        ]);
    }
}
