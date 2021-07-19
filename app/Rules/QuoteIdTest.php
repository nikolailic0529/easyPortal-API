<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\QuoteId
 */
class QuoteIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.quote_id' => 'Translated',
                ],
            ];
        });
        $this->assertEquals($this->app->make(QuoteId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @param array<string, mixed> $settings
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $quoteFactory, array $settings = []): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $quoteId      = $quoteFactory($this, $organization);
        $this->setSettings($settings);
        $this->assertEquals($expected, $this->app->make(QuoteId::class)->passes('test', $quoteId));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'       => [
                true,
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $type     = Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                    ]);
                    return $document->getKey();
                },
                [
                    'ep.quote_types' => ['f9834bc1-2f2f-4c57-bb8d-7a224ac24983'],
                ],
            ],
            'not-exists'   => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted' => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $type     = Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                    ]);
                    $document->delete();
                    return $document->id;
                },
                [
                    'ep.quote_types' => ['f9834bc1-2f2f-4c57-bb8d-7a224ac24983'],
                ],
            ],
        ];
    }
    // </editor-fold>
}
