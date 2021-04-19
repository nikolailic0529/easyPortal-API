<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Schema\Location;

trait WithLocations {
    use Polymorphic;

    protected ?LocationFactory $locations = null;

    public function setLocationFactory(?LocationFactory $factory): static {
        $this->locations = $factory;

        return $this;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\Location> $locations
     *
     * @return array<\App\Models\Location>
     */
    protected function objectLocations(Model $owner, array $locations): array {
        return $this->polymorphic(
            $owner,
            $locations,
            static function (Location $location): ?string {
                return $location->locationType;
            },
            function (Model $object, Location $location): ?LocationModel {
                return $this->location($object, $location);
            },
        );
    }

    protected function location(Model $owner, Location $location): ?LocationModel {
        return $this->locations
            ? $this->locations->create($owner, $location)
            : null;
    }
}
