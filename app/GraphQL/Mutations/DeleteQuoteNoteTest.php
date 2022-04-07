<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\File;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteQuoteNote
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteQuoteNoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $dataFactory = null,
        bool $exists = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $note = null;

        if ($dataFactory) {
            $note = $dataFactory($this, $org, $user);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation deleteQuoteNote($input: DeleteQuoteNoteInput!) {
                deleteQuoteNote(input:$input) {
                    deleted
                }
            }', ['input' => ['id' => $note?->getKey() ?: $this->faker->uuid()]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertEquals($exists, $note->exists());
            self::assertEquals(
                $exists,
                File::query()
                    ->where('object_id', '=', $note->getKey())
                    ->where('object_type', '=', $note->getMorphClass())
                    ->exists(),
            );
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteQuoteNote'),
                new OrgUserDataProvider('deleteQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess('deleteQuoteNote', DeleteContractNote::class, [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $organization, ?User $user): Note {
                            $data = [];
                            if ($organization) {
                                $data['organization_id'] = $organization->getKey();
                            }
                            if ($user) {
                                $user->save();
                                $data['user_id'] = $user->getKey();
                            }

                            return Note::factory()->for($user)->hasFiles(1)->create($data);
                        },
                        false,
                    ],
                    'Different User' => [
                        new GraphQLUnauthorized('deleteQuoteNote'),
                        static function (TestCase $test, ?Organization $organization, ?User $user): Note {
                            $data = [];
                            if ($organization) {
                                $data['organization_id'] = $organization->getKey();
                            }

                            return Note::factory()->hasFiles(1)->for(User::factory())->create($data);
                        },
                        true,
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteQuoteNote'),
                new OrgUserDataProvider('deleteQuoteNote', [
                    'org-administer',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess('deleteQuoteNote', DeleteContractNote::class, [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $organization, ?User $user): Note {
                            $data = [];
                            if ($organization) {
                                $data['organization_id'] = $organization->getKey();
                            }
                            if ($user) {
                                $user->save();
                                $data['user_id'] = $user->getKey();
                            }

                            return Note::factory()->hasFiles(1)->create($data);
                        },
                        false,
                    ],
                    'Different User' => [
                        new GraphQLSuccess('deleteQuoteNote', DeleteContractNote::class, [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $organization, ?User $user): Note {
                            $data = [];
                            if ($organization) {
                                $data['organization_id'] = $organization->getKey();
                            }

                            return Note::factory()->hasFiles(1)->for(User::factory())->create($data);
                        },
                        false,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
