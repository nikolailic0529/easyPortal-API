<?php declare(strict_types = 1);

namespace Tests\Constraints\ContentTypes;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;

class XlsxContentType extends ContentType {
    public function __construct() {
        parent::__construct('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
