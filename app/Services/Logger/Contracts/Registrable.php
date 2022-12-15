<?php declare(strict_types = 1);

namespace App\Services\Logger\Contracts;

interface Registrable {
    public static function register(): void;
}
