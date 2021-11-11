<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

class Phone extends Value {
    public function __invoke(): string {
        return $this->faker->e164PhoneNumber;
    }
}
