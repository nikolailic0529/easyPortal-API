<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use LogicException;

use function sprintf;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait MorphMapRequired {
    public function getMorphClass(): string {
        $class = parent::getMorphClass();

        if ($class === static::class) {
            /**
             * Storing class names in a database is a very bad idea. You should
             * add a name for the model into MorphMap.
             *
             * @see \App\Providers\AppServiceProvider::boot()
             */
            throw new LogicException(sprintf(
                'Please add morph name for `%s` model.',
                static::class,
            ));
        }

        return $class;
    }
}
