<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

class Latitude extends Value {
    public function __invoke(): string {
        return (string) $this->faker->latitude;
    }
}
