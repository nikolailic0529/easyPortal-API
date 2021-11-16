<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use App\Services\Service as BaseService;
use DateInterval;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

use function array_merge;

class Service extends BaseService {
    public function __construct(
        Config $config,
        Cache $cache,
        protected Locale $locale,
    ) {
        parent::__construct($config, $cache);
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

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval($this->config->get('ep.cache.graphql.ttl') ?: 'P1W');
    }
}
