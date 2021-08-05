<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use App\Models\User;
use App\Services\Filesystem\ModelDiskFactory;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ForbiddenResponse;
use Tests\DataProviders\Http\Organizations\OrganizationDataProvider;
use Tests\DataProviders\Http\Users\OrganizationUserDataProvider;
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

        $id = $this->faker->uuid;

        if ($prepare) {
            $id = $prepare($this, $organization, $user);
        } else {
            // Required because some permissions check inside the controller.
            Note::factory()->hasFiles(1, ['id' => $id])->create();
        }

        $url = $this->app->make(UrlGenerator::class);
        $this->getJson($url->route('files', ['id' => $id]))->assertThat($expected);
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
            $note     = Note::factory()->create([
                'organization_id' => $organization->getKey(),
                'document_id'     => $document->getKey(),
                'user_id'         => $user->getKey(),
            ]);
            $file     = $test->app()->make(ModelDiskFactory::class)->getDisk($note)->storeToFile(
                UploadedFile::fake()->create('test.txt'),
            );

            return $file->getKey();
        };

        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider(),
                new OrganizationUserDataProvider([
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
                new OrganizationUserDataProvider([
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
