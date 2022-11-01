<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use OpenSpout\Reader\CSV\Reader;

class CsvAttachmentContent extends SpreadsheetContent {
    protected function getReader(): Reader {
        return new Reader();
    }
}
