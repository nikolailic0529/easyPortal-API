<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Language;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LanguageResolver extends Resolver implements SingletonPersistent {
    public function get(string $code, Closure $factory = null): ?Language {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Language::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Language::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'code' => new ClosureKey($this->normalizer, static function (Language $language): array {
                return [$language->code];
            }),
        ];
    }
}
