<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Resources\Auth\SignupResource;
use App\Http\Resources\RedirectResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth0Management;
use Auth0\Login\Auth0Service;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Spa\Http\Resources\Scalar\NullResource;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\DataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\CreatedResponse;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ForbiddenResponse;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\NotFoundResponse;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ValidationErrorResponse;
use Mockery;
use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\AuthController
 */
class AuthControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================

    /**
     * @covers ::signin
     * @dataProvider dataProviderSignin
     */
    public function testSignin(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        // Prepare
        $tenant = $tenantFactory($this);
        $user   = $userFactory ? $userFactory($this) : null;
        $url    = $this->getTenantUrl($tenant, '/auth/signin');

        // Mock
        $auth0  = Mockery::mock(Auth0Service::class);
        $method = 'login';

        if ($expected instanceof OkResponse) {
            $auth0->shouldReceive($method)->once()->andReturn(
                new RedirectResponse('http://example.com/'),
            );
        } else {
            $auth0->shouldReceive($method)->never();
        }

        $this->app->bind(Auth0Service::class, static function () use ($auth0): Auth0Service {
            return $auth0;
        });

        // Test
        if ($user) {
            $this->actingAs($user);
        }

        $this->getJson($url)->assertThat($expected);
    }

    /**
     * @covers ::signup
     * @dataProvider dataProviderSignup
     */
    public function testSignup(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $dataFactory = null,
    ): void {
        // Prepare
        /** @var \App\Models\Organization $tenant */
        $tenant  = $tenantFactory($this);
        $user    = $userFactory ? $userFactory($this) : null;
        $data    = $dataFactory ? $dataFactory($this) : [];
        $url     = $this->getTenantUrl($tenant, '/auth/signup');
        $auth0Id = Str::random();

        // Mock
        $service = Mockery::mock(Auth0Management::class);
        $method  = 'createUser';

        if ($expected instanceof CreatedResponse) {
            $service->shouldReceive($method)->once()->andReturnUsing(
                function (array $params) use ($tenant, $data, $auth0Id) {
                    // FIXME [auth0] Test connection after "Specify connection"

                    $this->assertTrue(Arr::has($params, 'app_metadata.uuid'));
                    $this->assertTrue(Arr::has($params, 'app_metadata.tenant'));
                    $this->assertEquals($tenant->getKey(), Arr::get($params, 'app_metadata.tenant'));
                    $this->assertEquals([
                        'phone'    => $data['phone'],
                        'company'  => $data['company'],
                        'reseller' => $data['reseller'],
                    ], $params['user_metadata']);

                    return [
                        'blocked'        => true,
                        'email'          => $data['email'],
                        'email_verified' => false,
                        'family_name'    => $data['given_name'],
                        'given_name'     => $data['family_name'],
                        'picture'        => 'https://example.com/avatar.png',
                        'created_at'     => '2021-02-09T08:00:59.054Z',
                        'updated_at'     => '2021-02-09T08:00:59.054Z',
                        'user_id'        => $auth0Id,
                    ];
                },
            );
        } else {
            $service->shouldReceive($method)->never();
        }

        $this->app->bind(Auth0Management::class, static function () use ($service): Auth0Management {
            return $service;
        });

        // Test
        if ($user) {
            $this->actingAs($user);
        }

        $this->postJson($url, $data)->assertThat($expected);

        // Check that user created
        if ($expected instanceof CreatedResponse) {
            $user = User::query()->orderByKeyDesc()->first();

            $this->assertNotNull($user);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderSignin(): array {
        return (new CompositeDataProvider(
            $this->getTenantDataProvider(),
            $this->getGuestDataProvider(),
            new ArrayDataProvider([
                'redirect to login' => [
                    new OkResponse(RedirectResource::class),
                ],
            ]),
        ))->getData();
    }


    /**
     * @return array<mixed>
     */
    public function dataProviderSignup(): array {
        $dataFactory = static function (self $test): array {
            return [
                'given_name'  => $test->faker->firstName,
                'family_name' => $test->faker->lastName,
                'email'       => $test->faker->email,
                'phone'       => $test->faker->e164PhoneNumber,
                'company'     => $test->faker->company,
                'reseller'    => $test->faker->company,
            ];
        };

        return (new CompositeDataProvider(
            $this->getTenantDataProvider(),
            $this->getGuestDataProvider(),
            new ArrayDataProvider([
                'invalid request'                     => [
                    new ExpectedFinal(new ValidationErrorResponse()),
                    static function (self $test) use ($dataFactory): array {
                        $data = $dataFactory($test);
                        $key  = $test->faker->randomElement(array_keys($data));

                        unset($data[$key]);

                        return $data;
                    },
                ],
                'valid request but local user exists' => [
                    new ExpectedFinal(new ValidationErrorResponse([
                        'email' => null,
                    ])),
                    static function (self $test) use ($dataFactory): array {
                        $data = $dataFactory($test);

                        User::factory()->create([
                            'email'           => $data['email'],
                            'organization_id' => Organization::factory()->create(),
                        ]);

                        return $data;
                    },
                ],
                'valid request'                       => [
                    new CreatedResponse(SignupResource::class),
                    $dataFactory,
                ],
            ]),
        ))->getData();
    }

    protected function getGuestDataProvider(): DataProvider {
        return new ArrayDataProvider([
            'guest is allowed'    => [
                new Unknown(),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed' => [
                new ExpectedFinal(new ForbiddenResponse()),
                static function (): ?User {
                    return User::factory()->make();
                },
            ],
        ]);
    }

    protected function getUserDataProvider(): DataProvider {
        return new ArrayDataProvider([
            'guest is not allowed' => [
                new ExpectedFinal(new Unauthorized()),
                static function (): ?User {
                    return null;
                },
            ],
            'user is allowed'      => [
                new Unknown(),
                static function (): ?User {
                    return User::factory()->make();
                },
            ],
        ]);
    }

    protected function getTenantDataProvider(): DataProvider {
        return new ArrayDataProvider([
            'no tenant' => [
                new ExpectedFinal(new NotFoundResponse()),
                static function (): ?Organization {
                    return null;
                },
            ],
            'tenant'    => [
                new Unknown(),
                static function (self $test): ?Organization {
                    return Organization::factory()->create([
                        'subdomain' => $test->faker->word,
                    ]);
                },
            ],
        ]);
    }

    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getTenantUrl(?Organization $tenant, string $path): string {
        // TODO [tests] Replace to something more implicit.

        return $tenant
            ? "http://{$tenant->subdomain}.example.com{$path}"
            : $path;
    }
    // </editor-fold>
}
