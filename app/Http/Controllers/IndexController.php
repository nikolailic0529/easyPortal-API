<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\GraphQL\Queries\Application as ApplicationQuery;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Http\Request;
use Illuminate\View\View;


class IndexController extends Controller {
    /**
     * @return \View|array<string,string>
     */
    public function index(ApplicationQuery $query, Request $request, Factory $view): View|array {
        $response = $query(null, []);

        if (!$request->expectsJson()) {
            (new RegisterErrorViewPaths())();

            $response = $view->make('index', $response);
        }

        return $response;
    }
}
