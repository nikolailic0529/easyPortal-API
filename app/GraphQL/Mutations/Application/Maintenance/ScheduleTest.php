<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application\Maintenance;

use App\Services\Maintenance\Maintenance;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\Maintenance\Schedule
 */
class ScheduleTest extends TestCase {
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
        array $input = null,
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Lighthouse performs validation BEFORE permission check :(
        //
        // https://github.com/nuwave/lighthouse/issues/1780
        //
        // Following code required to "fix" it
        $input ??= [
            'message' => $this->faker->sentence(),
            'start'   => $this->faker->iso8601(),
            'end'     => $this->faker->iso8601(),
        ];

        if ($expected instanceof GraphQLSuccess) {
            $this->override(Maintenance::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('schedule')
                    ->once()
                    ->andReturn(true);
            });
        } else {
            $this->override(Maintenance::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('schedule')
                    ->never();
            });
        }

        $this
            ->graphQL(
                /** @lang GraphQL */
                '
                mutation schedule($input: ApplicationMaintenanceScheduleInput!) {
                    application {
                        maintenance {
                            schedule(input: $input) {
                                result
                            }
                        }
                    }
                }',
                [
                    'input' => $input,
                ],
            )
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
                        new JsonFragmentSchema('maintenance.schedule', self::class),
                        new JsonFragment('maintenance.schedule', [
                            'result' => true,
                        ]),
                    ),
                    [
                        'message' => 'message',
                        'start'   => '2021-10-19T10:15:00+00:00',
                        'end'     => '2021-11-19T10:15:00+00:00',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
