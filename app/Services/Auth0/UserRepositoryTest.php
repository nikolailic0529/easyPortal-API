<?php declare(strict_types = 1);

namespace App\Services\Auth0;

use App\Models\User;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;
use Tests\WithCurrentTenant;

/**
 * @internal
 * @coversDefaultClass \App\Services\Auth0\UserRepository
 */
class UserRepositoryTest extends TestCase {
    use WithCurrentTenant;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getUserByIdentifier
     *
     * @dataProvider dataProviderGetUserByIdentifier
     */
    public function testGetUserByIdentifier(bool $expected, int|string $id, ?Closure $userFactory): void {
        $repository = Mockery::mock(UserRepository::class);

        $repository->shouldAllowMockingProtectedMethods();
        $repository->makePartial();

        $repository->shouldReceive('getAuth0UserInfo')->once()->andReturn([
            'profile' => [
                'sub' => $id,
            ],
        ]);

        $user   = $userFactory ? $userFactory($this) : null;
        $actual = $repository->getUserByIdentifier($id);

        if ($expected) {
            $this->assertEquals($user, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    /**
     * @covers ::getUserByUserInfo
     *
     * @dataProvider dataProviderGetUserByUserInfo
     *
     * @param array<string, mixed> $userInfo
     */
    public function testGetUserByUserInfo(bool|Exception $expected, array $userInfo, ?Closure $userFactory): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $user       = $userFactory ? $userFactory($this) : null;
        $repository = $this->app->make(UserRepository::class);
        $actual     = $repository->getUserByUserInfo($userInfo);

        $this->assertEquals($user, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGetUserByUserInfo(): array {
        return [
            'user is not exists'                 => [
                (new ModelNotFoundException())->setModel(User::class),
                ['profile' => ['sub' => '123']],
                static function (): ?User {
                    return null;
                },
            ],
            'user exists by identifier is wrong' => [
                (new ModelNotFoundException())->setModel(User::class),
                ['profile' => ['sub' => '123']],
                static function (): ?User {
                    return User::factory()->create();
                },
            ],
            'user exists'                        => [
                true,
                ['profile' => ['sub' => '123']],
                static function (): ?User {
                    return User::factory()->create(['sub' => '123']);
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGetUserByIdentifier(): array {
        return [
            'user is not exists'                 => [
                false,
                '123',
                static function (): ?User {
                    return null;
                },
            ],
            'user exists by identifier is wrong' => [
                false,
                '123',
                static function (): ?User {
                    return User::factory()->create();
                },
            ],
            'user exists'                        => [
                true,
                '123',
                static function (): ?User {
                    return User::factory()->create(['sub' => '123']);
                },
            ],
        ];
    }
    // </editor-fold>
}
