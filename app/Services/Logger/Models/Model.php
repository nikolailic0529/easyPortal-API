<?php declare(strict_types = 1);

namespace App\Services\Logger\Models;

use App\Services\Logger\Logger;
use App\Utils\Eloquent\Concerns\UuidAsPrimaryKey;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;

abstract class Model extends LaraASPModel {
    use UuidAsPrimaryKey;

    protected const CASTS = [
        // empty
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $connection = Logger::CONNECTION;

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

    /**
     * @inheritDoc
     */
    public function save(array $options = []): bool {
        return static::withoutEvents(function () use ($options) {
            $connection = $this->getConnection();
            $dispatcher = $connection->getEventDispatcher();

            try {
                $connection->unsetEventDispatcher();

                return parent::save($options);
            } finally {
                $connection->setEventDispatcher($dispatcher);
            }
        });
    }
}
