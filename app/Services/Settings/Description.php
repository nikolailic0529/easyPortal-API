<?php declare(strict_types = 1);

namespace App\Services\Settings;

use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionClassConstant;

use function trim;

class Description {
    public function __construct() {
        // empty
    }

    public function get(ReflectionClass|ReflectionClassConstant $object): ?string {
        $desc = null;

        if ($object->getDocComment()) {
            $doc  = DocBlockFactory::createInstance()->create($object);
            $desc = trim("{$doc->getSummary()}\n\n{$doc->getDescription()}");
        }

        return $desc;
    }
}
