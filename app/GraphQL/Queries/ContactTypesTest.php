<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Contact;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\ContactTypes
 */
class ContactTypesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $contactFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($contactFactory) {
            $contactFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                contactTypes {
                    id
                    name
                }
            }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('contactTypes', ContactTypes::class, [
                        'data' => [
                            'contactTypes' => [
                                [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name' => 'Contact1',
                                ],
                            ],
                        ],
                    ]),
                    static function (): void {
                        // This should
                        Type::factory()
                            ->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'        => 'Contact1',
                                'object_type' => (new Contact())->getMorphClass(),
                            ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
