<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\GraphQL\Mutations\CreateQuoteNote;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Testing\File as TestingFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ForbiddenResponse;
use Tests\DataProviders\Http\Organizations\OrganizationDataProvider;
use Tests\DataProviders\Http\Users\UserDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\FilesController
 */
class FilesControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        $id = null;
        if ($prepare) {
            $id = $prepare($this, $organization, $user);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $this->setOrganization(Organization::factory()->make());
            }
            Note::factory()
                ->hasFiles(1, [
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ff',
                ])
                ->create();
            $id = 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ff';
        }

        $url = $this->app->make(UrlGenerator::class);
        $this->getJson($url->route('files', ['id' => $id ]))->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare = static function (TestCase $test, ?Organization $organization, User $user): string {
            if ($user) {
                $user->save();
            }
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $document = Document::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'type_id'     => $type->getKey(),
                'reseller_id' => $reseller->getKey(),
            ]);

            $note = Note::factory()->create([
                'organization_id' => $organization->getKey(),
                'document_id'     => $document->getKey(),
                'user_id'         => $user->getKey(),
            ]);

            $helper = $test->app->make(CreateQuoteNote::class);
            $upload = TestingFile::create('document.csv');
            $file   = $helper->createFile($note, $upload);
            $file->save();
            return $file->getKey();
        };
        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider(),
                new UserDataProvider([
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new Ok(),
                        [
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
                        ],
                        $prepare,
                    ],
                    'different user' => [
                        new ForbiddenResponse(),
                        [
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
                        ],
                        static function (
                            TestCase $test,
                            ?Organization $organization,
                            ?User $user,
                        ) use ($prepare): string {
                            $user2 = User::factory()->create();
                            return $prepare($test, $organization, $user2);
                        },
                    ],
                    'wrong type'     => [
                        new ForbiddenResponse(),
                        [
                            'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
                        ],
                        $prepare,
                    ],
                ]),
            ),
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider(),
                new UserDataProvider([
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new Ok(),
                        [
                            'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
                        ],
                        $prepare,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
