<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

interface Upsertable {
    /**
     * @return array<int, string>
     */
    public static function getUniqueKey(): array;
}
