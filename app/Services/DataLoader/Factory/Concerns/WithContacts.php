<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Contact;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Schema\Types\CompanyContactPerson;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

trait WithContacts {
    use Polymorphic;

    abstract protected function getContactsFactory(): ContactFactory;

    /**
     * @param array<CompanyContactPerson> $persons
     *
     * @return Collection<array-key, Contact>
     */
    protected function objectContacts(Model $owner, array $persons): Collection {
        return $this->polymorphic(
            $owner,
            $persons,
            static function (CompanyContactPerson $person): string {
                return $person->type;
            },
            function (Model $owner, CompanyContactPerson $person): ?Contact {
                return $this->contact($owner, $person);
            },
        );
    }

    protected function contact(Model $owner, CompanyContactPerson $person): ?Contact {
        return $this->getContactsFactory()->create($owner, $person);
    }
}
