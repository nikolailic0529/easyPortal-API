<?php declare(strict_types = 1);

namespace App\Utils\Providers;

interface EventsProvider {
    /**
     * @return list<class-string|string>
     */
    public static function getEvents(): array;
}
