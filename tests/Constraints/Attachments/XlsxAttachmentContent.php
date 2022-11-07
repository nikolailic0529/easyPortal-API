<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use OpenSpout\Reader\XLSX\Reader;

class XlsxAttachmentContent extends SpreadsheetContent {
    protected function getReader(): Reader {
        return new Reader();
    }
}
