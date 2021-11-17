<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    /**
     * @covers ::getDefaultKey
     */
    public function testGetDefaultKey(): void {
        $locale  = $this->app->get(Locale::class);
        $service = new class($locale) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Locale $locale,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getDefaultKey(): array {
                return parent::getDefaultKey();
            }
        };

        $this->assertEquals([$locale], $service->getDefaultKey());
    }
}
