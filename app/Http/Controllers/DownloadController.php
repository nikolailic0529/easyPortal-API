<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use GraphQL\Server\OperationParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\GraphQL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function fopen;
use function fputcsv;
class DownloadController extends Controller {
    public function csv(
        Request $request,
        GraphQL $graphQL,
    ): Response {
        // execute first to check for errors
        $operationParam = OperationParams::create([
            'query'         => $request->get('query'),
            'operationName' => $request->get('operationName'),
            'variables'     => $request->get('variables'),
        ]);
        $result         = $graphQL->executeOperation($operationParam);
        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result);
        }

        $paginated = false;
        $data      = $result['data'];
        $root      = array_key_first($data);
        $items     = $data[$root];

        if (array_key_exists('data', $items)) {
            $paginated = true;
            $items     = $items['data'];
        }

        $callback = static function () use ($request, $graphQL, $paginated, $root, $items): void {
            $file = fopen('php://output', 'w');
            foreach ($items as $index => $item) {
                if ($index === 0) {
                    fputcsv($file, array_keys($item));
                }
                fputcsv($file, array_values($item));
            }
            if ($paginated) {
                $variables = $request->variables;
                $page      = array_key_exists('page', $variables) ? $variables['page'] + 1 : 1;
                // Should it throw an error since we need page to loop through them ?
                do {
                    $variables['page'] = $page;
                    $operationParam    = OperationParams::create([
                        'query'         => $request->get('query'),
                        'operationName' => $request->get('operationName'),
                        'variables'     => $variables,
                    ]);
                    $result            = $graphQL->executeOperation($operationParam);
                    $items             = $result['data'][$root];

                    if ($paginated) {
                        $items = $items['data'];
                    }
                    foreach ($items as $index => $item) {
                        fputcsv($file, array_values($item));
                    }
                    $page++;
                } while (!empty($items));
            }
        };
        $headers  = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=export.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        return new StreamedResponse($callback, 200, $headers);
    }
}
