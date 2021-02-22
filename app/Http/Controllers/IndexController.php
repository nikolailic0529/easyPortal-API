<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Composer\InstalledVersions;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Http\Request;
use Illuminate\View\View;

use function file_get_contents;
use function json_decode;

class IndexController extends Controller {
    /**
     * @return \View|array<string,string>
     */
    public function index(Application $app, Repository $config, Request $request, Factory $view): View|array {
        $name     = $config->get('app.name');
        $package  = json_decode(file_get_contents($app->basePath('composer.json')), true)['name'];
        $version  = InstalledVersions::getVersion($package);
        $response = [
            'name'    => $name,
            'version' => $version,
        ];

        if (!$request->expectsJson()) {
            (new RegisterErrorViewPaths())();

            $response = $view->make('index', $response);
        }

        return $response;
    }
}
