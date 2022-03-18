<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\CustomerId
 */
class CustomerIdTest extends TestCase {
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
                    'validation.customer_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(CustomerId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $customerFactory): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $customerId   = $customerFactory($this, $organization);
        self::assertEquals($expected, $this->app->make(CustomerId::class)->passes('test', $customerId));
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
                    $customer = Customer::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                    $customer->resellers()->attach($reseller);
                    return $customer->getKey();
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
                    $customer = Customer::factory()
                        ->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                    $customer->resellers()->attach($reseller);
                    $customer->delete();
                    return $customer->getKey();
                },
            ],
        ];
    }
    // </editor-fold>
}
