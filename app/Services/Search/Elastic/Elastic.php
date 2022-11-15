<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;

use function assert;

class Elastic {
    public static function response(Elasticsearch|Promise $response): Elasticsearch {
        if ($response instanceof Promise) {
            $result   = $response->wait();
            $response = new Elasticsearch();

            assert($result instanceof ResponseInterface);

            $response->setResponse($result);
        }

        return $response;
    }
}
