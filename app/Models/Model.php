<?php declare(strict_types = 1);

namespace App\Models;

use App\CurrentTenant;
use App\Models\Concerns\UuidAsPrimaryKey;
use App\Models\Scopes\TenantScope;
use LastDragon_ru\LaraASP\Eloquent\Model as LaraASPModel;

use function app;

abstract class Model extends LaraASPModel {
    use UuidAsPrimaryKey;

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
     * @inheritdoc
     */
    protected static function booted() {
        parent::boot();
        static::addGlobalScope(new TenantScope(app()->make(CurrentTenant::class)));
    }
}
