<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Team;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Me\Update
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $clientFactory = null,
        Closure $inputUserFactory = null,
        Closure $inputFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Input
        $map   = [];
        $files = [];
        $input = [
            'id'    => $inputUserFactory
                ? $inputUserFactory($this, $org, $user)->getKey()
                : $this->faker->uuid(),
            'input' => $inputFactory
                ? $inputFactory($this, $org, $user)
                : [],
        ];

        if (isset($input['input']['photo'])) {
            $map['0']                = ['variables.input.photo'];
            $files['0']              = $input['input']['photo'];
            $input['input']['photo'] = null;
        }

        $operations = [
            'operationName' => 'test',
            'variables'     => $input,
            'query'         => /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: MeUpdateInput!) {
                    me {
                        update(input: $input) {
                            result
                            me {
                                family_name
                                given_name
                            }
                        }
                    }
                }
                GRAPHQL
            ,
        ];

        // Test
        $this
            ->multipartGraphQL($operations, $map, $files)
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $properties        = ['photo', 'enabled', 'role_id', 'team_id'];
            $updatedUser       = User::query()->whereKey($input['id'])->firstOrFail();
            $updatedOrgUser    = OrganizationUser::query()
                ->where('organization_id', '=', $org->getKey())
                ->where('user_id', '=', $input['id'])
                ->firstOrFail();
            $expected          = Arr::except($input['input'], ['photo']);
            $orgUserAttributes = array_keys(Arr::only($expected, $properties));
            $userAttributes    = array_keys(Arr::except($expected, $properties));
            $actual            = array_merge(
                array_combine($userAttributes, array_map(
                    static fn(string $attr) => $updatedUser->getAttribute($attr),
                    $userAttributes,
                )),
                array_combine($orgUserAttributes, array_map(
                    static fn(string $attr) => $updatedOrgUser->getAttribute($attr),
                    $orgUserAttributes,
                )),
            );

            self::assertEquals($expected, $actual);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $factory  = static function (self $test, Organization $organization, User $user): User {
            OrganizationUser::factory()->create([
                'organization_id' => $organization,
                'user_id'         => $user,
            ]);
            Team::factory()->create([
                'id'   => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                'name' => 'Team',
            ]);

            return $user;
        };
        $client   = static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getUserById')
                ->twice()
                ->andReturn(new KeycloakUser());
            $mock
                ->shouldReceive('updateUser')
                ->once()
                ->andReturn(true);
        };
        $settings = [
            'ep.image.max_size' => 200,
            'ep.image.formats'  => ['jpg'],
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('me'),
            new AuthMeDataProvider('me'),
            new ArrayDataProvider([
                'All possible properties'     => [
                    new GraphQLSuccess(
                        'me',
                        new JsonFragment('update', [
                            'result' => true,
                            'me'     => [
                                'given_name'  => 'Updated Given Name',
                                'family_name' => 'Updated Family Name',
                            ],
                        ]),
                    ),
                    $settings,
                    $client,
                    $factory,
                    static function (): array {
                        return [
                            'team_id'        => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                            'given_name'     => 'Updated Given Name',
                            'family_name'    => 'Updated Family Name',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 100),
                            'homepage'       => 'dashboard',
                            'timezone'       => 'Europe/London',
                            'locale'         => 'en_GB',
                        ];
                    },
                ],
                'part of possible properties' => [
                    new GraphQLSuccess(
                        'me',
                        new JsonFragment('update.result', true),
                    ),
                    $settings,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->atLeast()
                            ->once()
                            ->andReturn(new KeycloakUser());
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                    $factory,
                    static function (self $test): array {
                        $properties = [
                            'team_id'        => $test->faker->randomElement([
                                'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                null,
                            ]),
                            'given_name'     => $test->faker->firstName(),
                            'family_name'    => $test->faker->lastName(),
                            'title'          => $test->faker->randomElement([$test->faker->title(), null]),
                            'academic_title' => $test->faker->randomElement([$test->faker->word(), null]),
                            'office_phone'   => $test->faker->randomElement([$test->faker->e164PhoneNumber(), null]),
                            'mobile_phone'   => $test->faker->randomElement([$test->faker->e164PhoneNumber(), null]),
                            'contact_email'  => $test->faker->randomElement([$test->faker->email(), null]),
                            'job_title'      => $test->faker->randomElement([$test->faker->word(), null]),
                            'homepage'       => $test->faker->randomElement([$test->faker->url(), null]),
                            'timezone'       => $test->faker->randomElement([$test->faker->timezone(), null]),
                        ];
                        $count      = $test->faker->numberBetween(1, count($properties));
                        $keys       = $test->faker->randomElements(array_keys($properties), $count);
                        $updated    = Arr::only($properties, $keys);

                        return $updated;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
