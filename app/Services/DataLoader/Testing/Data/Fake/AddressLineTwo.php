<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

class AddressLineTwo extends Value {
    public function __invoke(): string {
        return $this->faker->secondaryAddress;
    }
}
