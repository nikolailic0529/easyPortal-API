<?php declare(strict_types = 1);

namespace Tests\Constraints\ContentTypes;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;

class CsvContentType extends ContentType {
    public function __construct() {
        parent::__construct('text/csv');
    }
}
