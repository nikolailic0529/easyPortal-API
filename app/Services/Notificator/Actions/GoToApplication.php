<?php declare(strict_types = 1);

namespace App\Services\Notificator\Actions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Action;

class GoToApplication extends Action {
    public function __construct(Repository $config) {
        parent::__construct($config->get('app.name'), $config->get('app.url'));
    }
}
