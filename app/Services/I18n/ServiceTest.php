<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Contracts\Translatable;
use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Service
 */
class ServiceTest extends TestCase {
    /**
     * @covers ::getTranslatableModels
     */
    public function testGetTranslatableModels(): void {
        $actual   = $this->app->make(Service::class)->getTranslatableModels();
        $expected = Models::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->implementsInterface(Translatable::class);
            })
            ->keys()
            ->all();

        self::assertEquals($expected, $actual);
    }
}
