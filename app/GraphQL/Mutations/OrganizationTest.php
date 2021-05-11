<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Currency;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
use function is_null;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Organization
 */
class OrganizationTest extends TestCase {
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

            if (array_key_exists('branding_logo', $data) && !is_null($data['branding_logo'])) {
                $map['0']              = ['variables.branding_logo'];
                $file['0']             = $data['branding_logo'];
                $data['branding_logo'] = null;
            }

            if (array_key_exists('branding_favicon', $data) && !is_null($data['branding_favicon'])) {
                $map['1']                 = ['variables.branding_favicon'];
                $file['1']                = $data['branding_favicon'];
                $data['branding_favicon'] = null;
            }
        }

        $query = /** @lang GraphQL */'mutation organization(
            $locale: String,
            $currency_id: ID
            $branding_dark_theme: Boolean
            $branding_primary_color: String
            $branding_secondary_color: String
            $website_url: String
            $email: String
            $branding_logo: Upload
            $branding_favicon: Upload
        ) {
            organization(
                locale: $locale,
                currency_id: $currency_id,
                branding_dark_theme: $branding_dark_theme,
                branding_primary_color: $branding_primary_color,
                branding_secondary_color: $branding_secondary_color
                website_url: $website_url
                email: $email
                branding_logo: $branding_logo,
                branding_favicon: $branding_favicon
            )
        }';

        $operations = [
            'operationName' => 'organization',
            'query'         => $query,
            'variables'     => $data,
        ];
        Storage::fake('local');

        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $organization = $organization->fresh();
            $this->assertEquals($data['locale'], $organization->locale);
            $this->assertEquals($data['currency_id'], $organization->currency_id);
            $this->assertEquals($data['branding_dark_theme'], $organization->branding_dark_theme);
            $this->assertEquals($data['branding_primary_color'], $organization->branding_primary_color);
            $this->assertEquals($data['branding_secondary_color'], $organization->branding_secondary_color);
            $this->assertEquals($data['website_url'], $organization->website_url);
            $this->assertEquals($data['email'], $organization->email);
            Storage::disk('local')->assertExists($organization->branding_logo);
            Storage::disk('local')->assertExists($organization->branding_favicon);
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
            new OrganizationDataProvider('organization'),
            new UserDataProvider('organization', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('organization', Organization::class, json_encode(true)),
                    static function (): array {
                        $currency = Currency::factory()->create();
                        return [
                            'locale'                   => 'en',
                            'currency_id'              => $currency->id,
                            'branding_dark_theme'      => false,
                            'branding_primary_color'   => '#ffffff',
                            'branding_secondary_color' => '#ffffff',
                            'website_url'              => 'https://www.example.com',
                            'email'                    => 'test@example.com',
                            'branding_logo'            => UploadedFile::fake()->create('branding_logo.jpg', 20),
                            'branding_favicon'         => UploadedFile::fake()->create('branding_favicon.jpg', 100),
                        ];
                    },
                ],
                'invalid request/Invalid color'    => [
                    new GraphQLError('branding_primary_color', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'branding_primary_color' => 'Color',
                        ];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLError('locale', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'locale' => 'en_UKX',
                        ];
                    },
                ],
                'invalid request/Invalid currency' => [
                    new GraphQLError('currency_id', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'currency_id' => 'wrongId',
                        ];
                    },
                ],
                'invalid request/Invalid format'   => [
                    new GraphQLError('branding_logo', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);
                        return [
                            'branding_logo'    => UploadedFile::fake()->create('branding_logo.jpg', 200),
                            'branding_favicon' => UploadedFile::fake()->create('branding_favicon.jpg', 200),
                        ];
                    },
                ],
                'invalid request/Invalid size'     => [
                    new GraphQLError('branding_favicon', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);
                        return [
                            'branding_logo'    => UploadedFile::fake()->create('branding_logo.png', $maxSize + 1024),
                            'branding_favicon' => UploadedFile::fake()
                                ->create('branding_favicon.jpg', $maxSize + 1024),
                        ];
                    },
                ],
                'invalid request/Invalid url'      => [
                    new GraphQLError('branding_favicon', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'website_url' => 'wrong url',
                        ];
                    },
                ],
                'invalid request/Invalid email'    => [
                    new GraphQLError('branding_favicon', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'email' => 'wrong mail',
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('organization', Organization::class, json_encode(true)),
                    static function (): array {
                        $currency = Currency::factory()->create();
                        return [
                            'locale'                   => 'en',
                            'currency_id'              => $currency->id,
                            'branding_dark_theme'      => null,
                            'branding_primary_color'   => null,
                            'branding_secondary_color' => null,
                            'website_url'              => 'https://www.example.com',
                            'email'                    => 'test@example.com',
                            'branding_logo'            => null,
                            'branding_favicon'         => null,
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
