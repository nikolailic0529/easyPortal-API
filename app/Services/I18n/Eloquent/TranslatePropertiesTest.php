<?php declare(strict_types = 1);

namespace App\Services\I18n\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Eloquent\TranslateProperties
 */
class TranslatePropertiesTest extends TestCase {
    /**
     * @covers ::getDefaultTranslations
     */
    public function testGetDefaultTranslations(): void {
        $model = Mockery::mock(TranslatePropertiesTest__Model::class);
        $model->shouldAllowMockingProtectedMethods();
        $model->makePartial();
        $model
            ->shouldReceive('getAttribute')
            ->with('a')
            ->once()
            ->andReturn('untranslated-a');
        $model
            ->shouldReceive('getTranslatableProperties')
            ->once()
            ->andReturn([
                'a',
            ]);
        $model
            ->shouldReceive('getTranslatableKey')
            ->once()
            ->andReturn('key');
        $model
            ->shouldReceive('getMorphClass')
            ->once()
            ->andReturn('A');

        self::assertEquals(
            [
                'models.A.key.a' => 'untranslated-a',
            ],
            $model->getDefaultTranslations(),
        );
    }

    /**
     * @covers ::getTranslatedProperty
     */
    public function testGetTranslatedProperty(): void {
        $model = Mockery::mock(TranslatePropertiesTest__Model::class);
        $model->shouldAllowMockingProtectedMethods();
        $model->makePartial();
        $model
            ->shouldReceive('getAttribute')
            ->with('b')
            ->once()
            ->andReturn('untranslated-b');
        $model
            ->shouldReceive('getTranslatableProperties')
            ->twice()
            ->andReturn([
                'a',
            ]);
        $model
            ->shouldReceive('getTranslatableKey')
            ->once()
            ->andReturn('key');
        $model
            ->shouldReceive('getMorphClass')
            ->once()
            ->andReturn('A');

        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'models.A.key.a' => 'translated-a',
                ],
            ];
        });

        self::assertEquals('translated-a', $model->getTranslatedProperty('a'));
        self::assertEquals('untranslated-b', $model->getTranslatedProperty('b'));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @see          https://github.com/mockery/mockery/issues/1022
 */
abstract class TranslatePropertiesTest__Model extends Model {
    use TranslateProperties;
}
