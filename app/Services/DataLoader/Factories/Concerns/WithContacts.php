<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Contact;
use App\Models\Model;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Schema\CompanyContactPerson;

trait WithContacts {
    use Polymorphic;

    protected ?ContactFactory $contacts = null;

    public function setContactsFactory(?ContactFactory $factory): static {
        $this->contacts = $factory;

        return $this;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyContactPerson> $persons
     *
     * @return array<\App\Models\Contact>
     */
    protected function objectContacts(Model $owner, array $persons): array {
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
        return $this->contacts
            ? $this->contacts->create($owner, $person)
            : null;
    }
}
