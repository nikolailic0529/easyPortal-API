<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

use function array_combine;
use function array_keys;
use function array_map;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\User\Update
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed>|null $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $clientFactory = null,
        Closure $inputUserFactory = null,
        Closure $inputFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Input
        $map   = [];
        $files = [];
        $input = [
            'id'    => $inputUserFactory
                ? $inputUserFactory($this, $organization, $user)->getKey()
                : $this->faker->uuid,
            'input' => $inputFactory
                ? $inputFactory($this, $organization, $user)
                : [],
        ];

        if (isset($input['input']['photo'])) {
            $map['0']                = ['variables.input.photo'];
            $files['0']              = $input['input']['photo'];
            $input['input']['photo'] = null;
        }

        $operations = [
            'operationName' => 'updateUser',
            'variables'     => $input,
            'query'         => /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation updateUser($id: ID!, $input: UserUpdateInput!) {
                    user(id: $id) {
                        update(input: $input) {
                            result
                            user {
                                given_name
                                family_name
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
            /** @var \App\Models\User $updated */
            $updated    = User::query()->whereKey($input['id'])->firstOrFail();
            $expected   = Arr::except($input['input'], ['photo']);
            $attributes = array_keys($expected);
            $values     = array_map(static fn(string $attr) => $updated->getAttribute($attr), $attributes);
            $actual     = array_combine($attributes, $values);

            $this->assertEquals($expected, $actual);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $factory  = static function (): User {
            return User::factory()->create();
        };
        $client   = static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getUserById')
                ->once()
                ->andReturn(new KeyCloakUser());
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
            new RootOrganizationDataProvider('user'),
            new OrganizationUserDataProvider('user', [
                'administer',
            ]),
            new ArrayDataProvider([
                'All possible properties'            => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('update', self::class),
                        new JsonFragment('update', [
                            'result' => true,
                            'user'   => [
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
                            'enabled'        => true,
                            'given_name'     => 'Updated Given Name',
                            'family_name'    => 'Updated Family Name',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'department'     => 'HR',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 100),
                            'homepage'       => 'dashboard',
                            'timezone'       => 'Europe/London',
                            'locale'         => 'en_GB',
                        ];
                    },
                ],
                'part of possible properties'        => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('update', self::class),
                        new JsonFragment('update.result', true),
                    ),
                    $settings,
                    $client,
                    $factory,
                    static function (self $test): array {
                        $properties = [
                            'enabled'        => $test->faker->boolean,
                            'given_name'     => $test->faker->firstName,
                            'family_name'    => $test->faker->lastName,
                            'title'          => $test->faker->randomElement([$test->faker->title, null]),
                            'academic_title' => $test->faker->randomElement([$test->faker->word, null]),
                            'office_phone'   => $test->faker->randomElement([$test->faker->e164PhoneNumber, null]),
                            'mobile_phone'   => $test->faker->randomElement([$test->faker->e164PhoneNumber, null]),
                            'contact_email'  => $test->faker->randomElement([$test->faker->email, null]),
                            'department'     => $test->faker->randomElement([$test->faker->word, null]),
                            'job_title'      => $test->faker->randomElement([$test->faker->word, null]),
                            'homepage'       => $test->faker->randomElement([$test->faker->url, null]),
                            'timezone'       => $test->faker->randomElement([$test->faker->timezone, null]),
                        ];
                        $count      = $test->faker->numberBetween(1, count($properties));
                        $keys       = $test->faker->randomElements(array_keys($properties), $count);
                        $updated    = Arr::only($properties, $keys);

                        return $updated;
                    },
                ],
                '`enabled` cannot be update by self' => [
                    new GraphQLValidationError('user'),
                    null,
                    null,
                    static function (TestCase $test, ?Organization $organization, ?User $user): User {
                        return $user ?? User::factory()->make();
                    },
                    static function (): array {
                        return [
                            'enabled' => true,
                        ];
                    },
                ],
                'User not found'                     => [
                    new GraphQLError('user', static function (): Throwable {
                        return new ObjectNotFound((new User())->getMorphClass());
                    }),
                    null,
                    null,
                    null,
                    null,
                ],

            ]),
        ))->getData();
    }
    // </editor-fold>
}
