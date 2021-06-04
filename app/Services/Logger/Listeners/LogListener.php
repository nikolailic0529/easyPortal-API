<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;

class LogListener extends Listener {
    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(MessageLogged::class, $this->getSafeListener(function (MessageLogged $event): void {
            $this->log($event);
        }));
    }

    protected function log(MessageLogged $event): void {
        $this->logger->count([
            "logs.{$event->level}" => 1,
        ]);
    }
}
