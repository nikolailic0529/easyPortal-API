<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\Organization\OrganizationProvider;
use App\Services\Service as BaseService;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class Service extends BaseService {
    protected function getKeyPart(object|string $value): string {
        $part = '';

        if ($value instanceof OrganizationProvider) {
            $part = $value->isRoot()
                ? '00000000-0000-0000-0000-000000000000'
                : $value->getKey();
        } elseif ($value instanceof BaseDirective) {
            $part = "@{$value->name()}";
        } else {
            $part = parent::getKeyPart($value);
        }

        return $part;
    }
}
