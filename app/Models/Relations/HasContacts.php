<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Contact;
use App\Utils\Eloquent\Concerns\SyncMorphMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use function count;

/**
 * @property int $contacts_count
 *
 * @mixin Model
 */
trait HasContacts {
    use SyncMorphMany;

    /**
     * @return MorphMany<Contact>
     */
    public function contacts(): MorphMany {
        return $this->morphMany(Contact::class, 'object');
    }

    /**
     * @param Collection<int,Contact> $contacts
     */
    public function setContactsAttribute(Collection $contacts): void {
        $this->syncMorphMany('contacts', $contacts);
        $this->contacts_count = count($contacts);
    }
}
