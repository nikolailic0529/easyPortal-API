<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Schema\Location;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

trait WithLocations {
    use Polymorphic;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getDispatcher(): Dispatcher;

    abstract protected function getLocationFactory(): LocationFactory;

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
                try {
                    return $this->location($object, $location);
                } catch (Throwable $exception) {
                    $this->getDispatcher()->dispatch(new ObjectSkipped($location, $exception));
                    $this->getLogger()->notice('Failed to process Location.', [
                        'owner'     => [$object->getMorphClass(), $object->getKey()],
                        'location'  => $location,
                        'exception' => $exception,
                    ]);
                }

                return null;
            },
        );
    }

    protected function location(Model $owner, Location $location): ?LocationModel {
        return $this->getLocationFactory()->create($owner, $location);
    }
}
