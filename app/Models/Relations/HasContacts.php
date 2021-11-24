<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Concerns\SyncMorphMany;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @property int $contacts_count
 *
 * @mixin \App\Models\Model
 */
trait HasContacts {
    use SyncMorphMany;

    public function contacts(): MorphMany {
        return $this->morphMany(Contact::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Contact> $contacts
     */
    public function setContactsAttribute(Collection|array $contacts): void {
        $this->syncMorphMany('contacts', $contacts);
        $this->contacts_count = count($contacts);
    }
}
