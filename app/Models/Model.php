<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\CascadeDeletes\CascadeDeletes;
use App\Models\Concerns\HideGeneratedAttributes;
use App\Models\Concerns\MorphMapRequired;
use App\Models\Concerns\SmartSave\SmartSave;
use App\Models\Concerns\UuidAsPrimaryKey;
use Illuminate\Database\Eloquent\SoftDeletes;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;

abstract class Model extends LaraASPModel {
    use SmartSave;
    use SoftDeletes;
    use CascadeDeletes;
    use UuidAsPrimaryKey;
    use MorphMapRequired;
    use HideGeneratedAttributes;

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
