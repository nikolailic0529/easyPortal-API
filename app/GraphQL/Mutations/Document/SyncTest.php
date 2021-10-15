<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Services\DataLoader\Jobs\DocumentUpdate;
use Closure;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Document\Sync
 */
class SyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $input = [],
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        Queue::fake();

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation sync($input: [DocumentSyncInput!]!) {
                    document {
                        sync(input: $input) {
                            result
                        }
                    }
                }',
                [
                    'input' => $input ?: [['id' => '79b91f78-c244-4e95-a99d-bf8b15255591']],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Queue::assertPushed(DocumentUpdate::class);
        } else {
            Queue::assertNothingPushed();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('document'),
            new RootUserDataProvider('document'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'document',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
