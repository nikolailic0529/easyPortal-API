<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

interface OwnedByReseller extends OwnedBy {
    public static function getOwnedByResellerColumn(): string;
}
