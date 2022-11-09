<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

class XlsxAttachmentContent extends SpreadsheetContent {
    protected function getReader(): Reader {
        $options                             = new Options();
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;

        return new Reader($options);
    }
}
