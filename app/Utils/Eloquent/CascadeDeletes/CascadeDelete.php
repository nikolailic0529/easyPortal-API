<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class CascadeDelete {
    public function __construct() {
        // empty
    }
}
