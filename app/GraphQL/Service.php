<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use App\Services\Service as BaseService;
use Illuminate\Contracts\Cache\Repository;

use function array_merge;

class Service extends BaseService {
    public function __construct(
        Repository $cache,
        protected Locale $locale,
    ) {
        parent::__construct($cache);
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultKey(): array {
        return array_merge(parent::getDefaultKey(), [
            // TODO [!] AppVersion,
            $this->locale,
        ]);
    }
}
