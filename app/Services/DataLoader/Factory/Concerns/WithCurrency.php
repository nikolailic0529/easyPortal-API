<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Currency;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;

use function mb_strtoupper;

/**
 * @mixin Factory
 */
trait WithCurrency {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getCurrencyResolver(): CurrencyResolver;

    protected function currency(?string $code): ?Currency {
        // Null?
        if ($code === null || $code === '') {
            return null;
        }

        // Find/Create
        return $this->getCurrencyResolver()->get($code, static function () use ($code): Currency {
            $model       = new Currency();
            $model->code = mb_strtoupper($code);
            $model->name = $code;

            $model->save();

            return $model;
        });
    }
}
