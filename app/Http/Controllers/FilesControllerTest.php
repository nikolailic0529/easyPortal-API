<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
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
use Tests\DataProviders\Http\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\Http\Users\OrgUserDataProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\Http\Controllers\FilesController
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class FilesControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                                $orgFactory
     * @param UserFactory                                        $userFactory
     * @param SettingsFactory                                    $settingsFactory
     * @param Closure(static, ?Organization, ?User): string|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $id = $this->faker->uuid();

        if ($prepare) {
            $id = $prepare($this, $org, $user);
        } else {
            // Required because some permissions check inside the controller.
            Note::factory()->hasFiles(1, ['id' => $id])->create();
        }

        $url = $this->app->make(UrlGenerator::class);
        $this->getJson($url->route('file', ['file' => $id]))->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare = static function (TestCase $test, ?Organization $org, User $user): string {
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            ]);
            $document = Document::factory()->ownedBy($org)->create([
                'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'type_id' => $type->getKey(),
            ]);
            $note     = Note::factory()->create([
                'organization_id' => $org->getKey(),
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
                new AuthOrgDataProvider(),
                new OrgUserDataProvider([
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
                new AuthOrgDataProvider(),
                new OrgUserDataProvider([
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
