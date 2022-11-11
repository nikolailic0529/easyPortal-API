<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use OpenSpout\Reader\CSV\Options;
use OpenSpout\Reader\CSV\Reader;

class CsvAttachmentContent extends SpreadsheetContent {
    protected function getReader(): Reader {
        $options                             = new Options();
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;

        return new Reader($options);
    }
}
