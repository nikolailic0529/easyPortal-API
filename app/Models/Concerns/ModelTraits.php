<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Concerns\CascadeDeletes\CascadeDeletes;
use App\Models\Concerns\SmartSave\SmartSave;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use HideGeneratedAttributes;
}
