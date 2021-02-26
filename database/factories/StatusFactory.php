<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Status;

class StatusFactory extends TypeFactory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Status::class;
}
