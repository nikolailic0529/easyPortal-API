<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use Exception;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\PdfContentType;
use PHPUnit\Framework\Constraint\Constraint;
use SplFileInfo;

class PdfAttachment extends Attachment {
    public function __construct(string $name) {
        parent::__construct($name);
    }

    protected function getContentTypeConstraint(): ContentType {
        return new PdfContentType();
    }

    protected function getAttachmentContentConstraint(SplFileInfo|string $content): Constraint {
        throw new Exception('Not yet supported.');
    }
}
