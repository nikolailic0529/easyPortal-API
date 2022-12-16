<?php declare(strict_types = 1);

namespace App\Utils\Providers;

interface Registrable {
    public static function register(): void;
}
