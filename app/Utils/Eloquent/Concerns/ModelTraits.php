<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\CascadeDeletes\CascadeDeletes;
use App\Utils\Eloquent\SmartSave\SmartSave;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @mixin \App\Models\Model
 * @mixin \App\Models\Pivot
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
