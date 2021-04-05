<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Settings
*/
class SettingsTest extends TestCase {
    /**
     * @covers ::invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $expected
    */
    public function testInvoke(array $expected): void {
        $output = $this->app->make(Settings::class)(null, []);
        $this->assertEquals($expected, $output);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        // Prepare provider so we can use it when have setting implemented
        return [
            'ok' => [
                [
                    ['name' => 'ValueA', 'value' => 123],
                    ['name' => 'ValueB', 'value' => 'asd'],
                ],
            ],
        ];
    }
    // </editor-fold>
}
