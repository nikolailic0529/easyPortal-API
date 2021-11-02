<?php declare(strict_types = 1);

namespace Tests\ResponseTypes;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;

class CsvContentType extends ContentType {
    public function __construct() {
        parent::__construct('text/csv');
    }
}
