<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use GraphQL\Server\Helper;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laragraph\Utils\RequestParser;
use Nuwave\Lighthouse\Events\StartRequest;
use Nuwave\Lighthouse\GraphQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function count;
use function fclose;
use function fopen;
use function fputcsv;
use function range;

class DownloadController extends Controller {
    public function index(
        Request $request,
        GraphQL $graphQL,
        EventsDispatcher $eventsDispatcher,
        RequestParser $requestParser,
        Helper $graphQLHelper,
    ): Response {
        $eventsDispatcher->dispatch(
            new StartRequest($request),
        );

        $result = $graphQL->executeRequest($request, $requestParser, $graphQLHelper);
        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result, 200);
        }

        // it will always return data
        $items    = [];
        $data     = $result['data'];
        $firstKey = array_key_first($data);

        if ($this->isAssoc($data[$firstKey])) {
            $items = $data[$firstKey]['data'];
        } else {
            $items = $data[$firstKey];
        }

        $headers  = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=export.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $callback = static function () use ($items): void {
            $file = fopen('php://output', 'w');
            foreach ($items as $index => $item) {
                if ($index === 0) {
                    fputcsv($file, array_keys($item));
                }
                fputcsv($file, array_values($item));
            }
            fclose($file);
        };
        return new StreamedResponse($callback, 200, $headers);
    }
    /**
     * @param array<mixed> $arr
     */
    protected function isAssoc(array $arr): bool {
        if (array() === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
