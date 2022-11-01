<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;
use SplFileInfo;
use Tests\Constraints\ContentTypes\CsvContentType;

class CsvAttachment extends Attachment {
    protected function getContentTypeConstraint(): ContentType {
        return new CsvContentType();
    }

    protected function getAttachmentContentConstraint(SplFileInfo|string $content): SpreadsheetContent {
        return new CsvAttachmentContent($content);
    }
}
