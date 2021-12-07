<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\GraphQL\Queries\Application\Application as ApplicationQuery;
use App\GraphQL\Queries\Application\Maintenance as MaintenanceQuery;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Http\Request;
use Illuminate\View\View;

use function array_merge;

class IndexController extends Controller {
    /**
     * @return \View|array<string,string>
     */
    public function index(
        Request $request,
        Factory $view,
        ApplicationQuery $query,
        MaintenanceQuery $maintenance,
    ): View|array {
        $response = array_merge($query(), [
            'maintenance' => $maintenance()?->toArray(),
        ]);

        if (isset($response['maintenance'])) {
            unset($response['maintenance']['notified']);
        }

        if (!$request->expectsJson()) {
            (new RegisterErrorViewPaths())();

            $response = $view->make('index', $response);
        }

        return $response;
    }
}
