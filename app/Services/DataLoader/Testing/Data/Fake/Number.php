<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

class Number extends Value {
    public function __invoke(): int {
        return $this->faker->randomNumber($this->faker->numberBetween(5, 10));
    }
}
