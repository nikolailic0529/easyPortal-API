<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data\Fake;

use Faker\Generator;

abstract class Value {
    public function __construct(
        protected Generator $faker,
    ) {
        // empty
    }

    abstract public function __invoke(): mixed;
}
