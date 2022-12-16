<?php declare(strict_types = 1);

namespace App\Http\Controllers\Horizon;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Laravel\Horizon\Http\Controllers\HomeController as HorizonHomeController;

use function ltrim;
use function parse_url;

use const PHP_URL_PATH;

class HomeController extends HorizonHomeController {
    public function __construct(
        protected Repository $config,
        protected UrlGenerator $url,
    ) {
        parent::__construct();
    }

    public function index(): mixed {
        // Horizon cannot run when Laravel installed into subdirectory
        //
        // https://github.com/laravel/horizon/issues/592
        $path = (string) $this->config->get('horizon.path');
        $path = $this->url->to($path);
        $path = parse_url($path, PHP_URL_PATH);
        $path = ltrim($path, '/');

        $this->config->set('horizon.path', $path);

        // Return
        return parent::index();
    }
}
