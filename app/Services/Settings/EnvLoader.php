<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class EnvLoader extends LoadEnvironmentVariables {
    public function load(Application $app): void {
        // If config is not cached env already loaded and we no need any actions.
        if (!$app->configurationIsCached()) {
            return;
        }

        // Check env file for current env
        if ($app->environment()) {
            $this->setEnvironmentFilePath(
                $app,
                "{$app->environmentFile()}.{$app->environment()}",
            );
        }

        // Load
        try {
            $this->createDotenv($app)->safeLoad();
        } catch (Exception $exception) {
            throw new SettingsFailedToLoadEnv(
                "{$app->environmentPath()}/{$app->environmentFile()}",
                $exception,
            );
        }
    }
}
