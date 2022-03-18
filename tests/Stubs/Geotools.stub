<?php declare(strict_types = 1);

// todo(Geohash): Create the PR to fix types (https://github.com/thephpleague/geotools/)

namespace League\Geotools\Geohash {
    use InvalidArgumentException;
    use League\Geotools\Coordinate\CoordinateInterface;
    use RuntimeException;

    class Geohash implements GeohashInterface {
        public const MIN_LENGTH = 1;
        public const MAX_LENGTH = 12;

        public function getGeohash(): string {
            // empty
        }

        public function getCoordinate(): CoordinateInterface {
            // empty
        }

        /**
         * @return array<CoordinateInterface>
         */
        public function getBoundingBox(): array {
            // empty
        }

        /**
         * @inheritDoc
         */
        public function encode(CoordinateInterface $coordinate, int $length = self::MAX_LENGTH): Geohash {
            // empty
        }

        /**
         * @inheritDoc
         */
        public function decode(string $geohash): Geohash {
            // empty
        }
    }

    interface GeohashInterface {
        /**
         * @throws InvalidArgumentException
         */
        public function encode(CoordinateInterface $coordinate, int $length): GeohashInterface;

        /**
         * @throws InvalidArgumentException
         * @throws RuntimeException
         */
        public function decode(string $geohash): GeohashInterface;
    }
}

namespace League\Geotools\Coordinate {
    interface CoordinateInterface {
        // empty
    }
}
