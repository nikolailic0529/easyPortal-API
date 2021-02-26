<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\UuidAsPrimaryKey;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;
use LogicException;

use function sprintf;

abstract class Model extends LaraASPModel {
    use UuidAsPrimaryKey;

    protected const CASTS = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Primary Key always UUID.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

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
