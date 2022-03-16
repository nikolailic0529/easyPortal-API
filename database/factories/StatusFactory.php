<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * @method Status create($attributes = [], ?Model $parent = null)
 * @method Status make($attributes = [], ?Model $parent = null)
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
