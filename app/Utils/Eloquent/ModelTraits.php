<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use App\Utils\Eloquent\CascadeDeletes\CascadeDeletes;
use App\Utils\Eloquent\Concerns\HideGeneratedAttributes;
use App\Utils\Eloquent\Concerns\MorphMapRequired;
use App\Utils\Eloquent\Concerns\UuidAsPrimaryKey;
use App\Utils\Eloquent\SmartSave\SmartSave;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @mixin \App\Utils\Eloquent\Model
 * @mixin \App\Utils\Eloquent\Pivot
 */
trait ModelTraits {
    use SmartSave;
    use UuidAsPrimaryKey;
    use SoftDeletes;
    use CascadeDeletes;
    use MorphMapRequired;
    use HasRelationships;
    use HideGeneratedAttributes;
}