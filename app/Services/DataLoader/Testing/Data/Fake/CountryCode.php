<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

class CountryCode extends Value {
    public function __invoke(): string {
        return $this->faker->countryCode;
    }
}
