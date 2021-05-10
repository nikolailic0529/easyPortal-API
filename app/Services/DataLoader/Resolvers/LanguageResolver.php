<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Language;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Support\Collection;

class LanguageResolver extends Resolver {
    public function get(string $code, Closure $factory = null): ?Language {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Language::query()->get();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'code' => new ClosureKey(static function (Language $language): string {
                return $language->code;
            }),
        ];
    }
}
