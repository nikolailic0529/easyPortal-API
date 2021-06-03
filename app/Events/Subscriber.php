<?php declare(strict_types = 1);

namespace App\Events;

use Illuminate\Contracts\Events\Dispatcher;

interface Subscriber {
    public function subscribe(Dispatcher $dispatcher): void;
}
