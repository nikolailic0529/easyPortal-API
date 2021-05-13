<?php declare(strict_types = 1);

namespace App\Services\Passwords;


use App\Models\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Passwords\TokenRepository
 */
class TokenRepositoryTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $hasher     = $this->app->make(Hasher::class);
        $repository = new TokenRepository(
            Mockery::mock(ConnectionInterface::class),
            $hasher,
            'table',
            'key',
        );

        $email = $this->faker->email;
        $user  = Mockery::mock(CanResetPassword::class);
        $user
            ->shouldReceive('getEmailForPasswordReset')
            ->twice()
            ->andReturn($email);

        PasswordReset::factory(2)->create([
            'email' => $email,
        ]);

        $token   = $repository->create($user);
        $created = PasswordReset::query()->first();

        $this->assertEquals($email, $created->email);
        $this->assertTrue($hasher->check($token, $created->token));
        $this->assertEquals(1, PasswordReset::query()->count());
    }

    /**
     * @covers ::recentlyCreatedToken
     */
    public function testRecentlyCreatedToken(): void {
        // Prepare
        $throttle   = 60;
        $repository = new TokenRepository(
            Mockery::mock(ConnectionInterface::class),
            $this->app->make(Hasher::class),
            'table',
            'key',
            60,
            $throttle,
        );

        $email = $this->faker->email;
        $user  = Mockery::mock(CanResetPassword::class);
        $user
            ->shouldReceive('getEmailForPasswordReset')
            ->atLeast()
            ->once()
            ->andReturn($email);

        // No token
        $this->assertFalse($repository->recentlyCreatedToken($user));

        // Non expired
        $token = PasswordReset::factory()->create([
            'email' => $email,
        ]);

        $this->assertTrue($repository->recentlyCreatedToken($user));

        $token->delete();

        // Expired
        $token = PasswordReset::factory()->create([
            'email'      => $email,
            'created_at' => Date::now()->subSeconds(2 * $throttle),
        ]);

        $this->assertFalse($repository->recentlyCreatedToken($user));

        $token->delete();
    }

    /**
     * @covers ::deleteExpired
     */
    public function testDeleteExpired(): void {
        // Prepare
        $expires    = 60;
        $repository = new TokenRepository(
            Mockery::mock(ConnectionInterface::class),
            $this->app->make(Hasher::class),
            'table',
            'key',
            $expires,
        );

        // Objects
        PasswordReset::factory()->create([
            'created_at' => Date::now()->subSeconds(2 * $expires * 60),
        ]);
        PasswordReset::factory()->create([
            'created_at' => Date::now(),
        ]);

        // Test
        $this->assertEquals(2, PasswordReset::query()->count());

        $repository->deleteExpired();

        $this->assertEquals(1, PasswordReset::query()->count());
    }

    /**
     * @covers ::exists
     */
    public function testExists(): void {
        $repository = new TokenRepository(
            Mockery::mock(ConnectionInterface::class),
            $this->app->make(Hasher::class),
            'table',
            'key',
        );

        $email = $this->faker->email;
        $userA = Mockery::mock(CanResetPassword::class);
        $userA
            ->shouldReceive('getEmailForPasswordReset')
            ->atLeast()
            ->once()
            ->andReturn($email);
        $userB = Mockery::mock(CanResetPassword::class);
        $userB
            ->shouldReceive('getEmailForPasswordReset')
            ->atLeast()
            ->once()
            ->andReturn($this->faker->email);

        // Another user
        $repository->create($userB);

        // No records (for user)
        $this->assertFalse($repository->exists($userA, 'abc'));

        // Exists
        $token = $repository->create($userA);

        $this->assertFalse($repository->exists($userA, 'abc'));
        $this->assertTrue($repository->exists($userA, $token));
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $repository = new TokenRepository(
            Mockery::mock(ConnectionInterface::class),
            $this->app->make(Hasher::class),
            'table',
            'key',
        );

        $email = $this->faker->email;
        $user  = Mockery::mock(CanResetPassword::class);
        $user
            ->shouldReceive('getEmailForPasswordReset')
            ->atLeast()
            ->once()
            ->andReturn($email);

        PasswordReset::factory(2)->create([
            'email' => $email,
        ]);

        $this->assertEquals(2, PasswordReset::query()->count());

        $repository->delete($user);

        $this->assertEquals(0, PasswordReset::query()->count());
    }
}
