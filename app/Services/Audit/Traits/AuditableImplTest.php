<?php declare(strict_types = 1);

namespace App\Services\Audit\Traits;

use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Audit\Contracts\Auditable;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Audit\Traits\AuditableImpl
 */
class AuditableImplTest extends TestCase {
    public function testIsDirty(): void {
        $model = new class() extends Model implements Auditable {
            use AuditableImpl;

            /**
             * @param array<string, array{
             *      type: string,
             *      added: array<string|int>,
             *      deleted: array<string|int>,
             *      }> $dirtyRelations
             */
            public function setDirtyRelations(array $dirtyRelations): void {
                $this->dirtyRelations = $dirtyRelations;
            }
        };

        self::assertFalse($model->isDirty());

        $model->setDirtyRelations([
            'relation' => [
                'type'    => 'Model',
                'added'   => [],
                'deleted' => [],
            ],
        ]);

        self::assertTrue($model->isDirty());
        self::assertFalse($model->isDirty('property'));
        self::assertFalse($model->isDirty(['property']));
    }

    public function testSetRelation(): void {
        // Prepare
        $model = new class() extends Model {
            use AuditableImpl;

            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
             *
             * @var string
             */
            protected $keyType = 'string';

            public function getMorphClass(): string {
                return 'Test';
            }
        };

        self::assertEquals([], $model->getDirtyRelations());

        // Not a Collection
        $model->setRelation('object', $model);
        $model->setRelation('object', clone $model);

        self::assertEquals([], $model->getDirtyRelations());

        // Collection
        $a = (clone $model)->setAttribute('id', 'b4ac8eaf-eb94-44a0-a53e-ae0a9bed6e35');
        $b = (clone $model)->setAttribute('id', '44b709df-fe9c-42d5-a5ea-b4fc2f0f326a');
        $c = (clone $model)->setAttribute('id', 'a6004b87-9d9c-4e4d-ba12-5501e5583077');
        $d = (clone $model)->setAttribute('id', '0fb4ff33-36bf-41f9-ac3c-517b99511755');

        self::assertInstanceOf(Model::class, $a);
        self::assertInstanceOf(Model::class, $b);
        self::assertInstanceOf(Model::class, $c);
        self::assertInstanceOf(Model::class, $d);

        // Initial
        $model->setRelation('objects', new Collection([$a, $b]));

        self::assertEquals([], $model->getDirtyRelations());

        // Same
        $model->setRelation('objects', new Collection([$b, $a]));

        self::assertEquals([], $model->getDirtyRelations());

        // Changed
        $model->setRelation('objects', new Collection([$b, $c, $d]));

        self::assertEquals(
            [
                'objects' => [
                    'type'    => $model->getMorphClass(),
                    'added'   => [$d->getKey(), $c->getKey()],
                    'deleted' => [$a->getKey()],
                ],
            ],
            $model->getDirtyRelations(),
        );
    }

    public function testSetRelationEagerLoading(): void {
        $user = User::factory()->create();

        OrganizationUser::factory()->create([
            'user_id' => $user,
        ]);

        GlobalScopes::callWithoutAll(static function () use ($user): void {
            $user->load('organizations');
        });

        self::assertEmpty($user->getDirtyRelations());
    }

    public function testSyncOriginal(): void {
        $model = new class() extends Model implements Auditable {
            use AuditableImpl;

            /**
             * @param array<string, array{
             *      type: string,
             *      added: array<string|int>,
             *      deleted: array<string|int>,
             *      }> $dirtyRelations
             */
            public function setDirtyRelations(array $dirtyRelations): void {
                $this->dirtyRelations = $dirtyRelations;
            }
        };

        $model->setDirtyRelations([
            'relation' => [
                'type'    => 'Model',
                'added'   => [],
                'deleted' => [],
            ],
        ]);

        self::assertNotEmpty($model->getDirtyRelations());
        self::assertSame($model, $model->syncOriginal());
        self::assertEmpty($model->getDirtyRelations());
    }
}
