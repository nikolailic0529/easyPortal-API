<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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

    /**
     * @covers ::isSlowQuery
     *
     * @dataProvider dataProviderIsSlowQuery
     */
    public function testIsSlowQuery(bool $expected, ?float $threshold, float $time): void {
        $this->setSettings([
            'ep.cache.graphql.threshold' => $threshold,
        ]);

        $this->assertEquals($expected, $this->app->make(Service::class)->isSlowQuery($time));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{bool,?float,float}>
     */
    public function dataProviderIsSlowQuery(): array {
        return [
            'null' => [true, null, 0],
            'zero' => [true, 0.0, -1],
            'slow' => [true, 1, 1.034],
            'fast' => [false, 1, 0.034],
        ];
    }
    // </editor-fold>
}
