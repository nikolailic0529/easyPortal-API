<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\I18n\Translation\TranslationDefaults
 */
class TranslationDefaultsTest extends TestCase {
    public function testLoadModels(): void {
        // Prepare
        $modelA  = new class() extends Model implements Translatable {
            public function getTranslatedProperty(string $property): string {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getDefaultTranslations(): array {
                return [
                    'a' => 'translated-a',
                ];
            }
        };
        $modelB  = new class() extends Model implements Translatable {
            public function getTranslatedProperty(string $property): string {
                return '';
            }

            /**
             * @inheritDoc
             */
            public function getDefaultTranslations(): array {
                return [
                    'b' => 'translated-b',
                ];
            }
        };
        $models  = [
            $modelA::class => $modelA,
            $modelB::class => $modelB,
        ];
        $loader  = $this->app->make(TranslationLoader::class);
        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('getTranslatableModels')
            ->twice()
            ->andReturn([
                $modelA::class,
                $modelB::class,
            ]);

        $default = new class($service, $loader, $models) extends TranslationDefaults {
            /**
             * @param array<Model&Translatable> $models
             */
            public function __construct(
                Service $service,
                TranslationLoader $loader,
                protected array $models,
            ) {
                parent::__construct($service, $loader);
            }

            /**
             * @inheritDoc
             */
            public function loadModels(string $locale): array {
                return parent::loadModels($locale);
            }

            protected function getModels(string $model): Collection {
                return new Collection(isset($this->models[$model]) ? [$this->models[$model]] : []);
            }
        };

        // Test
        $expected = [
            'a' => 'translated-a',
            'b' => 'translated-b',
        ];

        self::assertEquals($expected, $default->loadModels($this->app->getLocale()));
        self::assertEquals($expected, $default->loadModels('another'));
    }
}
