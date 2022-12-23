<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

interface Normalizer {
    public static function normalize(mixed $value): mixed;
}
