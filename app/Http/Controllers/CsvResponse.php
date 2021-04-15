<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Nuwave\Lighthouse\Support\Contracts\CreatesResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;

class CsvResponse implements CreatesResponse {
    /**
     * Create a HTTP response from the final result.
     *
     * @param  array<mixed>  $result
     */
    public function createResponse(array $result): Response {
        if (array_key_exists('errors', $result)) {
            return new JsonResponse($result, 200);
        }
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=export.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // it will always return data
        $items    = [];
        $data     = $result['data'];
        $firstKey = array_key_first($data);
        $items    = $data[$firstKey];

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
}
