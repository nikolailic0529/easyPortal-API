<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\DocumentId
 */
class DocumentIdTest extends TestCase {
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
                    'validation.document_id' => 'Translated',
                ],
            ];
        });
        $this->assertEquals($this->app->make(DocumentId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $documentFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $document     = $documentFactory($this, $organization);
        $this->assertEquals($expected, $this->app->make(DocumentId::class)->passes('test', $document));
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
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                    ]);
                    return $document->getKey();
                },
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
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                    ]);
                    $document->delete();
                    return $document->id;
                },
            ],
        ];
    }
    // </editor-fold>
}
