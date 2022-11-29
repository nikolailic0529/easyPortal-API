<?php declare(strict_types = 1);

namespace App\Models\Logs;

use App\Utils\Eloquent\Concerns\StringKey;
use App\Utils\Eloquent\Concerns\UuidAsPrimaryKey;
use App\Utils\Eloquent\SmartSave\SmartSave;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;

abstract class Model extends LaraASPModel {
    use StringKey;
    use SmartSave;
    use UuidAsPrimaryKey;

    public const CONNECTION = 'logs';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $connection = self::CONNECTION;

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
     * @var array<string, string|Castable|CastsAttributes>
     */
    protected $casts = [];
}
