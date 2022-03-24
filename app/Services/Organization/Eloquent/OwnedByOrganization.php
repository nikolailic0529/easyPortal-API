<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

interface OwnedByOrganization extends OwnedBy {
    public static function getOwnedByOrganizationColumn(): string;
}
