<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Testing\Database;

use App\Models\Casts\DocumentPrice as DocumentPriceCast;
use App\Services\Organization\Testing\Database\OwnedBy;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\Testing\DocumentPrice;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory as BaseFactory;

/**
 * @template TModel of Model
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

    /**
     * @inheritdoc
     */
    public function newModel(array $attributes = []) {
        $model = parent::newModel($attributes);
        $casts = $model->getCasts();

        foreach ($casts as $attr => $cast) {
            switch ($cast) {
                case DocumentPriceCast::class:
                    $casts[$attr] = DocumentPrice::class;
                    break;
                default:
                    unset($casts[$attr]);
                    break;
            }
        }

        return $model;
    }
}
