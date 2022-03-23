<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use App\Utils\Eloquent\CascadeDeletes\CascadeDeletes;
use App\Utils\Eloquent\Concerns\HideGeneratedAttributes;
use App\Utils\Eloquent\Concerns\QualifiedModelQuery;
use App\Utils\Eloquent\Concerns\StringKey;
use App\Utils\Eloquent\Concerns\UuidAsPrimaryKey;
use App\Utils\Eloquent\SmartSave\SmartSave;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @mixin Model
 * @mixin Pivot
 */
trait ModelTraits {
    use StringKey;
    use SmartSave;
    use UuidAsPrimaryKey;
    use SoftDeletes;
    use CascadeDeletes;
    use HasRelationships;
    use HideGeneratedAttributes;
    use QualifiedModelQuery;
}
