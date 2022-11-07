<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use Illuminate\Routing\ResponseFactory;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Header;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use SplFileInfo;
use Symfony\Component\HttpFoundation\HeaderUtils;

abstract class Attachment extends Response {
    public function __construct(string $name, SplFileInfo|string $content = null) {
        parent::__construct(...$this->getResponseConstraints($name, $content));
    }

    abstract protected function getContentTypeConstraint(): ContentType;

    abstract protected function getAttachmentContentConstraint(SplFileInfo|string $content): Constraint;

    /**
     * @return array<Constraint>
     */
    protected function getResponseConstraints(string $name, SplFileInfo|string $content = null): array {
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
            $this->getContentTypeConstraint(),
            new Header('Content-Disposition', [
                new IsEqual($disposition),
            ]),
        ];

        if ($content) {
            $constraints[] = $this->getAttachmentContentConstraint($content);
        }

        return $constraints;
    }
}
