<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Models\Reseller;
use App\Services\DataLoader\Events\InvalidDataFound;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\Logger\LoggerObject;

class DataLoaderInvalidDataObject implements LoggerObject {
    public function __construct(
        protected InvalidDataFound $event,
    ) {
        // empty
    }

    public function getId(): ?string {
        $data = $this->event->getData();

        return $data instanceof ResellerNotFoundException
            ? $data->getId()
            : null;
    }

    public function getType(): string {
        $data = $this->event->getData();
        $type = 'unknown';

        if ($data instanceof ResellerNotFoundException) {
            $type = (new Reseller())->getMorphClass();
        } else {
            // empty
        }

        return $type;
    }
}
