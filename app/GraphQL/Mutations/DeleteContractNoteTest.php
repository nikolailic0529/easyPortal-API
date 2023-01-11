<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

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
 * @covers \App\GraphQL\Mutations\DeleteContractNote
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteContractNoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
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
            ->graphQL(/** @lang GraphQL */ 'mutation deleteContractNote($input: DeleteContractNoteInput!) {
                deleteContractNote(input:$input) {
                    deleted
                }
            }', ['input' => ['id' => $note?->getKey() ?: $this->faker->uuid()]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertEquals($exists, $note->exists());
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
            'contracts-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteContractNote'),
                new OrgUserDataProvider('deleteContractNote', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('deleteContractNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->create([
                                'user_id' => $user,
                            ]);
                        },
                        false,
                    ],
                    'not owner'   => [
                        new GraphQLUnauthorized('deleteContractNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->for(User::factory())->create();
                        },
                        true,
                    ],
                    'system note' => [
                        new GraphQLUnauthorized('deleteContractNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        false,
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteContractNote'),
                new OrgUserDataProvider('deleteContractNote', [
                    'org-administer',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('deleteContractNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->create([
                                'user_id' => $user,
                            ]);
                        },
                        false,
                    ],
                    'not owner'   => [
                        new GraphQLSuccess('deleteContractNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->for(User::factory())->create();
                        },
                        false,
                    ],
                    'system note' => [
                        new GraphQLUnauthorized('deleteContractNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        false,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
