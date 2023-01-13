<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Closure;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Application\Logs
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class LogsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory         $orgFactory
     * @param UserFactory                 $userFactory
     * @param Closure(static ): void|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($prepare) {
            $prepare($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                application {
                    logs(where: { category: { equal: "Queue" } }) {
                        id
                        category
                        action
                        status
                        object_type
                        object_id
                        duration
                        created_at
                        finished_at
                        statistics
                        context
                    }
                    logsAggregated(where: { category: { equal: "Queue" } }) {
                        count
                        groups(groupBy: {object_id: asc}) {
                            key
                            count
                        }
                        groupsAggregated(groupBy: {object_id: asc}) {
                            count
                        }
                    }
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
            new AuthOrgRootDataProvider('application'),
            new AuthRootDataProvider('application'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'application',
                        [
                            'logs'           => [
                                [
                                    'id'          => '2cc07e2a-a482-4814-9697-314c0cec4e23',
                                    'category'    => 'Queue',
                                    'action'      => 'test',
                                    'status'      => 'unknown',
                                    'object_type' => null,
                                    'object_id'   => null,
                                    'duration'    => 123.45,
                                    'created_at'  => '2021-07-01T00:00:00+00:00',
                                    'finished_at' => null,
                                    'statistics'  => '{"Log":{"total":{"levels":1},"levels":{"error":1}}}',
                                    'context'     => '{"Log":{"total":{"levels":1},"levels":{"notice":1}}}',
                                ],
                            ],
                            'logsAggregated' => [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => null,
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ],
                    ),
                    static function (TestCase $test): void {
                        Log::factory()->create([
                            'category' => Category::eloquent(),
                        ]);

                        Log::factory()->create([
                            'parent_id' => Log::factory()->create([
                                'id'          => '2cc07e2a-a482-4814-9697-314c0cec4e23',
                                'category'    => Category::queue(),
                                'action'      => 'test',
                                'status'      => Status::unknown(),
                                'object_type' => null,
                                'object_id'   => null,
                                'duration'    => 123.45,
                                'created_at'  => Date::make('2021-07-01T00:00:00+00:00'),
                                'finished_at' => null,
                                'statistics'  => ['Log' => ['total' => ['levels' => 1], 'levels' => ['error' => 1]]],
                                'context'     => ['Log' => ['total' => ['levels' => 1], 'levels' => ['notice' => 1]]],
                            ]),
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
