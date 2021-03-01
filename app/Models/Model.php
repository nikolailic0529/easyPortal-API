<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\MorphMapRequired;
use App\Models\Concerns\UuidAsPrimaryKey;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;

abstract class Model extends LaraASPModel {
    use UuidAsPrimaryKey;
    use MorphMapRequired;

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
}
