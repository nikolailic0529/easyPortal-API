<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Status;

/**
 * @method \App\Models\Status create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Status make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
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
