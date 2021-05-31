<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Currency;
use App\Services\DataLoader\Client\Client;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
use function is_null;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateOrganization
 */
class UpdateOrganizationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $dataFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory($this));

        $this->setUser($userFactory, $organization);

        $data = [];
        $map  = [];
        $file = [];

        if ($dataFactory) {
            $data = $dataFactory($this);

            if (array_key_exists('branding', $data)) {
                if (array_key_exists('logo', $data['branding']) && !is_null($data['branding']['logo'])) {
                    $map['0']                 = ['variables.input.branding.logo'];
                    $file['0']                = $data['branding']['logo'];
                    $data['branding']['logo'] = null;
                }

                if (array_key_exists('favicon', $data['branding']) && !is_null($data['branding']['favicon'])) {
                    $map['1']                    = ['variables.input.branding.favicon'];
                    $file['1']                   = $data['branding']['favicon'];
                    $data['branding']['favicon'] = null;
                }

                if (
                    array_key_exists('welcome_image', $data['branding'])&&
                    !is_null($data['branding']['welcome_image'])
                ) {
                    $map['1']                          = ['variables.input.branding.welcome_image'];
                    $file['1']                         = $data['branding']['welcome_image'];
                    $data['branding']['welcome_image'] = null;
                }
            }
        }

        $query = /** @lang GraphQL */'mutation updateOrganization($input: UpdateOrganizationInput!){
            updateOrganization(input: $input){
              result
              organization {
                  id
                  name
                  email
                  root
                  locale
                  website_url
                  analytics_code
                  currency_id
                  currency {
                    id
                    name
                    code
                  }
                  locations {
                    id
                    state
                    postcode
                    line_one
                    line_two
                    latitude
                    longitude
                  }
                  branding {
                    dark_theme
                    main_color
                    secondary_color
                    logo_url
                    favicon_url
                    default_main_color
                    default_secondary_color
                    default_logo_url
                    default_favicon_url
                    welcome_image_url
                    welcome_heading
                    welcome_underline
                  }
              }
            }
          }';

        $operations = [
            'operationName' => 'updateOrganization',
            'query'         => $query,
            'variables'     => ['input' => $data ],
        ];

        // Fake
        Storage::fake('local');

        // Mocks
        if ($expected instanceof GraphQLSuccess) {
            $client = Mockery::mock(Client::class);
            $client
                ->shouldReceive('updateBrandingData')
                ->once()
                ->andReturn(true);
            $client
                ->shouldReceive('updateCompanyLogo')
                ->once()
                ->andReturn('https://example.com/logo.png');
            $this->app->bind(Client::class, static function () use ($client) {
                return $client;
            });
        }

        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $organization = $organization->fresh();
            $this->assertEquals($data['locale'], $organization->locale);
            $this->assertEquals($data['currency_id'], $organization->currency_id);
            $this->assertEquals($data['website_url'], $organization->website_url);
            $this->assertEquals($data['email'], $organization->email);
            $this->assertEquals($data['analytics_code'], $organization->analytics_code);
            if (array_key_exists('branding', $data)) {
                $this->assertEquals($data['branding']['dark_theme'], $organization->branding_dark_theme);
                $this->assertEquals($data['branding']['main_color'], $organization->branding_main_color);
                $this->assertEquals($data['branding']['secondary_color'], $organization->branding_secondary_color);
            }
            $this->assertEquals('https://example.com/logo.png', $organization->branding_logo_url);
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
            new OrganizationDataProvider('updateOrganization', '439a0a06-d98a-41f0-b8e5-4e5722518e01'),
            new UserDataProvider('updateOrganization', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('updateOrganization', UpdateOrganization::class),
                    static function (): array {
                        $currency = Currency::factory()->create();
                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'code',
                            'branding'       => [
                                'dark_theme'        => false,
                                'main_color'        => '#ffffff',
                                'secondary_color'   => '#ffffff',
                                'logo'              => UploadedFile::fake()->create('branding_logo.jpg', 20),
                                'favicon'           => UploadedFile::fake()->create('branding_favicon.jpg', 100),
                                'welcome_image'     => null,
                                'welcome_heading'   => 'heading',
                                'welcome_underline' => 'underline',
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid color'    => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'branding' => [
                                'main_color' => 'Color',
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'locale' => 'en_UKX',
                        ];
                    },
                ],
                'invalid request/Invalid currency' => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'currency_id' => 'wrongId',
                        ];
                    },
                ],
                'invalid request/deleted currency' => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        $currency = Currency::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                        $currency->delete();
                        return [
                            'currency_id' => $currency->id,
                        ];
                    },
                ],
                'invalid request/Invalid format'   => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);
                        return [
                            'branding' => [
                                'logo'    => UploadedFile::fake()->create('branding_logo.jpg', 200),
                                'favicon' => UploadedFile::fake()->create('branding_favicon.jpg', 200),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid size'     => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);
                        return [
                            'branding' => [
                                'logo'    => UploadedFile::fake()->create('logo.png', $maxSize + 1024),
                                'favicon' => UploadedFile::fake()
                                    ->create('favicon.jpg', $maxSize + 1024),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid url'      => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'website_url' => 'wrong url',
                        ];
                    },
                ],
                'invalid request/Invalid email'    => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'email' => 'wrong mail',
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('updateOrganization', UpdateOrganization::class),
                    static function (): array {
                        $currency = Currency::factory()->create();
                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'analytics_code',
                            'branding'       => [
                                // Logo cannot be null
                                'logo'              => UploadedFile::fake()->create('branding_logo.jpg', 200),
                                'dark_theme'        => false,
                                'main_color'        => null,
                                'secondary_color'   => null,
                                'favicon'           => null,
                                'welcome_image'     => null,
                                'welcome_heading'   => null,
                                'welcome_underline' => null,
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
