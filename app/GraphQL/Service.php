<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use App\Services\Organization\OrganizationProvider;
use App\Services\Service as BaseService;
use Illuminate\Contracts\Cache\Repository;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

use function array_merge;

class Service extends BaseService {
    public function __construct(
        Repository $cache,
        protected Locale $locale,
    ) {
        parent::__construct($cache);
    }

    protected function getKeyPart(object|string $value): string {
        $part = '';

        if ($value instanceof OrganizationProvider) {
            $part = $this->mergeKeyParts(
                'Organization',
                $value->isRoot()
                    ? '00000000-0000-0000-0000-000000000000'
                    : $value->getKey(),
            );
        } elseif ($value instanceof BaseDirective) {
            $part = "@{$value->name()}";
        } else {
            $part = parent::getKeyPart($value);
        }

        return $part;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultKey(): array {
        return array_merge(parent::getDefaultKey(), [
            // TODO [!] AppVersion,
            $this->locale->get(),
        ]);
    }
}
