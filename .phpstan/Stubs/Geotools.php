<?php declare(strict_types = 1);

// todo(Geohash): Create the PR to fix types (https://github.com/thephpleague/geotools/)

namespace League\Geotools\Geohash {
    use InvalidArgumentException;
    use League\Geotools\Coordinate\CoordinateInterface;
    use RuntimeException;

    interface GeohashInterface {
        /**
         * @throws InvalidArgumentException
         * @return static
         */
        public function encode(CoordinateInterface $coordinate, int $length): GeohashInterface;

        /**
         * @throws InvalidArgumentException
         * @throws RuntimeException
         * @return static
         */
        public function decode(string $geohash): GeohashInterface;
    }
}

namespace League\Geotools\Coordinate {
    interface CoordinateInterface {
        // empty
    }
}
