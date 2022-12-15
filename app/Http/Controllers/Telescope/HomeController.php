<?php declare(strict_types = 1);

namespace App\Http\Controllers\Telescope;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Laravel\Telescope\Http\Controllers\HomeController as TelescopeHomeController;

use function ltrim;
use function parse_url;

use const PHP_URL_PATH;

class HomeController extends TelescopeHomeController {
    public function __construct(
        protected Repository $config,
        protected UrlGenerator $url,
    ) {
        // empty
    }

    public function index(): mixed {
        // Telescope cannot run when Laravel installed into subdirectory
        $path = (string) $this->config->get('telescope.path');
        $path = $this->url->to($path);
        $path = parse_url($path, PHP_URL_PATH);
        $path = ltrim($path, '/');

        $this->config->set('telescope.path', $path);

        // Return
        return parent::index();
    }
}
