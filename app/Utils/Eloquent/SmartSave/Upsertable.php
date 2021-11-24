<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

interface Upsertable {
    /**
     * @return array<string>
     */
    public static function getUniqueKey(): array;
}
