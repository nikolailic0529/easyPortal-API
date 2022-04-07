<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Tag;
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
class TagsTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $tagsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($tagsFactory) {
            $tagsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                tags(where: {assets: { where: {}, count: {lessThan: 1} }}) {
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
            new AuthOrgDataProvider('tags'),
            new AuthMeDataProvider('tags'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('tags', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'tag1',
                        ],
                    ]),
                    static function (): void {
                        Tag::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'tag1',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
