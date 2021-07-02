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
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentPaginatedSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Logs
 */
class LogsTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $logFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($logFactory) {
            $logFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                application {
                    logs(where: { category: { eq: "Queue" } }) {
                        data {
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
                        paginatorInfo {
                            count
                            currentPage
                            firstItem
                            hasMorePages
                            lastItem
                            lastPage
                            perPage
                            total
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
            new RootOrganizationDataProvider('application'),
            new RootUserDataProvider('application'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'application',
                        new JsonFragmentPaginatedSchema('logs', self::class),
                        new JsonFragment('logs', [
                            'data'          => [
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
                            'paginatorInfo' => [
                                'count'        => 1,
                                'currentPage'  => 1,
                                'firstItem'    => 1,
                                'hasMorePages' => false,
                                'lastItem'     => 1,
                                'lastPage'     => 1,
                                'perPage'      => 25,
                                'total'        => 1,
                            ],
                        ]),
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
