<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use GraphQL\Server\Helper;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Http\Request;
use Laragraph\Utils\RequestParser;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Support\Contracts\CreatesResponse;
use Nuwave\Lighthouse\Support\Http\Controllers\GraphQLController;
use Symfony\Component\HttpFoundation\Response;



class DownloadController extends GraphQLController {
    public function __invoke(
        Request $request,
        GraphQL $graphQL,
        EventsDispatcher $eventsDispatcher,
        RequestParser $requestParser,
        Helper $graphQLHelper,
        CreatesResponse $createsResponse,
    ): Response {
        return parent::__invoke(
            $request,
            $graphQL,
            $eventsDispatcher,
            $requestParser,
            $graphQLHelper,
            new CsvResponse(),
        );
    }
}
