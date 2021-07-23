<?php declare(strict_types = 1);

namespace App\Models;

use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Models\Note
 */
class NoteTest extends TestCase {
    use WithoutOrganizationScope;

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $user = User::factory()->create();
        $note = Note::factory()
            ->hasFiles(4)
            ->create([
                'user_id' => $user,
            ]);

        $note->delete();

        $this->assertEquals(0, Note::query()->count());
        $this->assertEquals(0, File::query()->count());
        // it won't affect another relation
        $this->assertTrue($user->fresh()->exists());
    }
}
