<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\OemGroup;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class OemGroupsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory        $orgFactory
     * @param UserFactory                $userFactory
     * @param Closure(static): void|null $factory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($factory) {
            $factory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                oemGroups {
                    id
                    key
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
            new AuthOrgDataProvider('oemGroups'),
            new AuthMeDataProvider('oemGroups'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('oemGroups', [
                        [
                            'id'   => 'bb23fe19-3ab5-4d31-b378-f40f8ce5ace6',
                            'key'  => 'b',
                            'name' => 'group b',
                        ],
                        [
                            'id'   => 'f3fb70cf-a1d5-4a66-bfd8-d7842082e708',
                            'key'  => 'a',
                            'name' => 'group a',
                        ],
                    ]),
                    static function (): void {
                        OemGroup::factory()->create([
                            'id'   => 'f3fb70cf-a1d5-4a66-bfd8-d7842082e708',
                            'key'  => 'a',
                            'name' => 'group a',
                        ]);
                        OemGroup::factory()->create([
                            'id'   => 'bb23fe19-3ab5-4d31-b378-f40f8ce5ace6',
                            'key'  => 'b',
                            'name' => 'group b',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
