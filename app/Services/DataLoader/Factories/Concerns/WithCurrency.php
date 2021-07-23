<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Currency;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CurrencyResolver;

/**
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithCurrency {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getCurrencyResolver(): CurrencyResolver;

    protected function currency(?string $code): ?Currency {
        $currency = null;

        if ($code !== null) {
            $currency = $this->getCurrencyResolver()->get($code, $this->factory(function () use ($code): Currency {
                $model       = new Currency();
                $normalizer  = $this->getNormalizer();
                $model->code = $normalizer->string($code);
                $model->name = $normalizer->string($code);

                $model->save();

                return $model;
            }));
        }

        return $currency;
    }
}
