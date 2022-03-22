<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use LastDragon_ru\LaraASP\Eloquent\Pivot as LaraASPPivot;

abstract class Pivot extends LaraASPPivot {
    use ModelTraits;

    protected const CASTS = [
        // empty
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
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<mixed>
     */
    protected $casts = self::CASTS;
}
