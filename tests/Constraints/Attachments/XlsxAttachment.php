<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use Illuminate\Routing\ResponseFactory;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Header;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use PHPUnit\Framework\Constraint\IsEqual;
use SplFileInfo;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Tests\Constraints\ContentTypes\XlsxContentType;

class XlsxAttachment extends Response {
    public function __construct(string $name, SplFileInfo|string $content = null) {
        $helper      = new class() extends ResponseFactory {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             * @phpstan-ignore-next-line
             */
            public function __construct() {
                // empty
            }

            public function fallbackName(mixed $name): string {
                return parent::fallbackName($name);
            }
        };
        $fallback    = $helper->fallbackName($name);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback);
        $constraints = [
            new XlsxContentType(),
            new Header('Content-Disposition', [
                new IsEqual($disposition),
            ]),
        ];

        if ($content) {
            $constraints[] = new XlsxAttachmentContent($content);
        }

        parent::__construct(...$constraints);
    }
}
