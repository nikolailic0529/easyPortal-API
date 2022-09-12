<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Testing\Database;

use App\Services\Organization\Testing\Database\OwnedBy;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory as BaseFactory;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @method TModel create($attributes = [], ?Model $parent = null)
 *
 * @extends BaseFactory<TModel>
 */
abstract class Factory extends BaseFactory {
    /**
     * @use OwnedBy<TModel>
     */
    use OwnedBy;

    /**
     * @inheritdoc
     */
    public function make($attributes = [], ?Model $parent = null): mixed {
        return GlobalScopes::callWithoutAll(function () use ($attributes, $parent): mixed {
            return parent::make($attributes, $parent);
        });
    }
}
