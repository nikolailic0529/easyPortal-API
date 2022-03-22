<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Contact;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @mixin TestCase
 */
trait HasContactsTests {
    use WithQueryLog;

    /**
     * @return Model&HasContacts
     */
    abstract protected function getModel(): Model;

    /**
     * @covers ::setContactsAttribute
     */
    public function testSetContactsAttribute(): void {
        /** @var Model&HasContacts $model */
        $model   = $this->getModel()->factory()->create([
            'contacts_count' => 2,
        ]);
        $morph   = $model->getMorphClass();
        $contact = Contact::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $used    = Contact::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);

        // Base
        self::assertEquals(2, Contact::query()->count());
        self::assertEqualsCanonicalizing([$contact, $used], $model->contacts->all());
        self::assertEquals(2, $model->contacts_count);

        // Used shouldn't be deleted
        $created         = Contact::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $model->contacts = [$created];

        $model->save();

        $used = $used->refresh();

        self::assertEquals([$created], $model->contacts->all());
        self::assertEquals(1, Contact::query()->count());
        self::assertNotNull($used->object_id);
        self::assertEquals($morph, $used->object_type);
    }
}
