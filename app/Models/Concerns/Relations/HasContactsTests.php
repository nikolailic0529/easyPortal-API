<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Contact;
use App\Models\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;

/**
 * @internal
 * @mixin \Tests\TestCase
 */
trait HasContactsTests {
    use WithQueryLog;

    /**
     * @return \App\Models\Model&\App\Models\Concerns\Relations\HasContacts
     */
    abstract protected function getModel(): Model;

    /**
     * @covers ::setContactsAttribute
     */
    public function testSetContactsAttribute(): void {
        /** @var \App\Models\Model&\App\Models\Concerns\Relations\HasContacts $model */
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
        $this->assertEquals(2, Contact::query()->count());
        $this->assertEqualsCanonicalizing([$contact, $used], $model->contacts->all());
        $this->assertEquals(2, $model->contacts_count);

        // Used shouldn't be deleted
        $created         = Contact::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $model->contacts = [$created];

        $model->save();

        $used = $used->refresh();

        $this->assertEquals([$created], $model->contacts->all());
        $this->assertEquals(1, Contact::query()->count());
        $this->assertNotNull($used->object_id);
        $this->assertEquals($morph, $used->object_type);
    }
}
