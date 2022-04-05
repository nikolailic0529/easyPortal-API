<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Testing\Database;

use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory as BaseFactory;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @method TModel create($attributes = [], ?Model $parent = null)
 * @method TModel make($attributes = [], ?Model $parent = null)
 *
 * @extends BaseFactory<TModel>
 */
abstract class Factory extends BaseFactory {
    // empty
}
