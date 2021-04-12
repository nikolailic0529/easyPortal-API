<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class EnvLoader extends LoadEnvironmentVariables {
    public function load(Application $app): void {
        // If config is not cached env vars already loaded and no need any actions.
        if (!$app->configurationIsCached()) {
            return;
        }

        /** @see LoadEnvironmentVariables::bootstrap() */
        $this->checkForSpecificEnvironmentFile($app);

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
