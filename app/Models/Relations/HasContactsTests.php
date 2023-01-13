<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Contact;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * @internal
 * @mixin TestCase
 */
trait HasContactsTests {
    /**
     * @return Model&HasContacts
     */
    abstract protected function getModel(): Model;

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
        $model->contacts = Collection::make([$created]);

        $model->save();

        $used = $used->refresh();

        self::assertEquals([$created], $model->contacts->all());
        self::assertEquals(1, Contact::query()->count());
        self::assertNotNull($used->object_id);
        self::assertEquals($morph, $used->object_type);
    }
}
