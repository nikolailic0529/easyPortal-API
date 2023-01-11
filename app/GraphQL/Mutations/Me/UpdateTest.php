<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Data\Team;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\Providers\Users\RootUserProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_keys;
use function count;
use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Me\Update
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                                              $orgFactory
     * @param UserFactory                                                      $userFactory
     * @param SettingsFactory                                                  $settingsFactory
     * @param Closure(Client&MockInterface): void|null                         $clientFactory
     * @param Closure(static, ?Organization, ?User): User|null                 $inputUserFactory
     * @param Closure(static, ?Organization, ?User): array<string, mixed>|null $inputFactory
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
        $input = [
            'id'    => $inputUserFactory
                ? $inputUserFactory($this, $org, $user)->getKey()
                : $this->faker->uuid(),
            'input' => $inputFactory
                ? $inputFactory($this, $org, $user)
                : [],
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: MeUpdateInput!) {
                    me {
                        update(input: $input) {
                            result
                            me {
                                family_name
                                given_name
                                title
                                academic_title
                                office_phone
                                mobile_phone
                                contact_email
                                job_title
                                homepage
                                timezone
                                locale
                                freshchat_id
                                team {
                                    id
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                $input,
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
        $factory  = static function (self $test, Organization $org, User $user): User {
            OrganizationUser::factory()->create([
                'organization_id' => $org,
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

        return (new MergeDataProvider([
            'user' => new CompositeDataProvider(
                new AuthOrgDataProvider('me'),
                new AuthMeDataProvider('me'),
                new ArrayDataProvider([
                    'All possible properties'     => [
                        new GraphQLSuccess(
                            'me',
                            new JsonFragment('update', [
                                'result' => true,
                                'me'     => [
                                    'given_name'     => 'Updated Given Name',
                                    'family_name'    => 'Updated Family Name',
                                    'title'          => 'Mr',
                                    'academic_title' => 'Professor',
                                    'office_phone'   => '+1-202-555-0197',
                                    'mobile_phone'   => '+1-202-555-0147',
                                    'contact_email'  => 'test@gmail.com',
                                    'job_title'      => 'Manger',
                                    'homepage'       => 'dashboard',
                                    'timezone'       => 'Europe/London',
                                    'locale'         => 'en_GB',
                                    'freshchat_id'   => '754afa26-c8aa-4b03-83d4-20206ca8ec79',
                                    'team'           => [
                                        'id' => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                    ],
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
                                'freshchat_id'   => '754afa26-c8aa-4b03-83d4-20206ca8ec79',
                            ];
                        },
                    ],
                    'Part of possible properties' => [
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
                            $phone      = $test->faker->randomElement([$test->faker->e164PhoneNumber(), null]);
                            $properties = [
                                'team_id'        => $test->faker->randomElement([
                                    'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                    null,
                                ]),
                                'given_name'     => $test->faker->firstName(),
                                'family_name'    => $test->faker->lastName(),
                                'title'          => $test->faker->randomElement([$test->faker->title(), null]),
                                'academic_title' => $test->faker->randomElement([$test->faker->word(), null]),
                                'office_phone'   => $phone,
                                'mobile_phone'   => $phone,
                                'contact_email'  => $test->faker->randomElement([$test->faker->email(), null]),
                                'job_title'      => $test->faker->randomElement([$test->faker->word(), null]),
                                'homepage'       => $test->faker->randomElement([$test->faker->url(), null]),
                                'timezone'       => $test->faker->randomElement([$test->faker->timezone(), null]),
                                'freshchat_id'   => $test->faker->uuid(),
                            ];
                            $count      = $test->faker->numberBetween(1, count($properties));
                            $keys       = $test->faker->randomElements(array_keys($properties), $count);
                            $updated    = Arr::only($properties, $keys);

                            return $updated;
                        },
                    ],
                    'Invalid input'               => [
                        new GraphQLValidationError('me', static function (Repository $config): array {
                            return [
                                'input.given_name'     => [
                                    trans('validation.required'),
                                ],
                                'input.family_name'    => [
                                    trans('validation.required'),
                                ],
                                'input.title'          => [
                                    trans('validation.required'),
                                ],
                                'input.academic_title' => [
                                    trans('validation.required'),
                                ],
                                'input.homepage'       => [
                                    trans('validation.required'),
                                ],
                                'input.job_title'      => [
                                    trans('validation.required'),
                                ],
                                'input.locale'         => [
                                    trans('validation.locale'),
                                ],
                                'input.mobile_phone'   => [
                                    trans('validation.phone'),
                                ],
                                'input.office_phone'   => [
                                    trans('validation.phone'),
                                ],
                                'input.team_id'        => [
                                    trans('validation.team_id'),
                                ],
                                'input.timezone'       => [
                                    trans('validation.timezone'),
                                ],
                                'input.photo'          => [
                                    trans('validation.max.file', [
                                        'max' => $config->get('ep.image.max_size') ?? 0,
                                    ]),
                                ],
                                'input.freshchat_id'   => [
                                    trans('validation.uuid'),
                                ],
                            ];
                        }),
                        $settings,
                        null,
                        static function (): User {
                            return User::factory()->make();
                        },
                        static function (): array {
                            return [
                                'team_id'        => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                'given_name'     => '',
                                'family_name'    => '',
                                'title'          => '',
                                'academic_title' => '',
                                'office_phone'   => 'not a phone',
                                'mobile_phone'   => 'not a phone',
                                'contact_email'  => 'test@gmail.com',
                                'job_title'      => '',
                                'photo'          => UploadedFile::fake()->create('photo.jpg', 250),
                                'homepage'       => '',
                                'timezone'       => 'Invalid/Timezone',
                                'locale'         => 'invalid_LOCALE',
                                'freshchat_id'   => 'not a uuid',
                            ];
                        },
                    ],
                ]),
            ),
            'root' => new CompositeDataProvider(
                new AuthOrgDataProvider('me'),
                new ArrayDataProvider([
                    'root is allowed' => [
                        new UnknownValue(),
                        new RootUserProvider(),
                    ],
                ]),
                new ArrayDataProvider([
                    'All possible properties' => [
                        new GraphQLSuccess(
                            'me',
                            new JsonFragment('update', [
                                'result' => true,
                                'me'     => [
                                    'given_name'     => 'Updated Given Name',
                                    'family_name'    => 'Updated Family Name',
                                    'title'          => 'Mr',
                                    'academic_title' => 'Professor',
                                    'office_phone'   => '+1-202-555-0197',
                                    'mobile_phone'   => '+1-202-555-0147',
                                    'contact_email'  => 'test@gmail.com',
                                    'job_title'      => 'Manger',
                                    'homepage'       => 'dashboard',
                                    'timezone'       => 'Europe/London',
                                    'locale'         => 'en_GB',
                                    'freshchat_id'   => '754afa26-c8aa-4b03-83d4-20206ca8ec79',
                                    'team'           => null,
                                ],
                            ]),
                        ),
                        $settings,
                        static function (MockInterface $mock): void {
                            $mock
                                ->shouldReceive('getUserById')
                                ->once()
                                ->andReturn(new KeycloakUser());
                            $mock
                                ->shouldReceive('updateUser')
                                ->once()
                                ->andReturn(true);
                        },
                        static function (self $test, Organization $org, User $user): User {
                            return $user;
                        },
                        static function (): array {
                            return [
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
                                'freshchat_id'   => '754afa26-c8aa-4b03-83d4-20206ca8ec79',
                            ];
                        },
                    ],
                    'Invalid input'           => [
                        new GraphQLValidationError('me', static function (Repository $config): array {
                            return [
                                'input.given_name'     => [
                                    trans('validation.required'),
                                ],
                                'input.family_name'    => [
                                    trans('validation.required'),
                                ],
                                'input.title'          => [
                                    trans('validation.required'),
                                ],
                                'input.academic_title' => [
                                    trans('validation.required'),
                                ],
                                'input.homepage'       => [
                                    trans('validation.required'),
                                ],
                                'input.job_title'      => [
                                    trans('validation.required'),
                                ],
                                'input.locale'         => [
                                    trans('validation.locale'),
                                ],
                                'input.mobile_phone'   => [
                                    trans('validation.phone'),
                                ],
                                'input.office_phone'   => [
                                    trans('validation.phone'),
                                ],
                                'input.team_id'        => [
                                    trans('validation.user_not_root'),
                                    trans('validation.team_id'),
                                ],
                                'input.timezone'       => [
                                    trans('validation.timezone'),
                                ],
                                'input.photo'          => [
                                    trans('validation.max.file', [
                                        'max' => $config->get('ep.image.max_size') ?? 0,
                                    ]),
                                ],
                                'input.freshchat_id'   => [
                                    trans('validation.uuid'),
                                ],
                            ];
                        }),
                        $settings,
                        null,
                        static function (): User {
                            return User::factory()->make();
                        },
                        static function (): array {
                            return [
                                'team_id'        => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                'given_name'     => '',
                                'family_name'    => '',
                                'title'          => '',
                                'academic_title' => '',
                                'office_phone'   => 'not a phone',
                                'mobile_phone'   => 'not a phone',
                                'contact_email'  => 'test@gmail.com',
                                'job_title'      => '',
                                'photo'          => UploadedFile::fake()->create('photo.jpg', 250),
                                'homepage'       => '',
                                'timezone'       => 'Invalid/Timezone',
                                'locale'         => 'invalid_LOCALE',
                                'freshchat_id'   => 'not a uuid',
                            ];
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
