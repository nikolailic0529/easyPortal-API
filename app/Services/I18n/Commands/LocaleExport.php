<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use App\Utils\Console\WithOptions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;

use function json_encode;
use function ksort;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class LocaleExport extends Command {
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:i18n-locale-export
        {--l|locale= : Locale to export (case sensitive)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Dump locale translations.';

    public function __invoke(
        Application $app,
        TranslationLoader $loader,
        TranslationDefaults $defaults,
    ): int {
        // Get
        $default      = $app->getLocale();
        $locale       = $this->getStringOption('locale', $default);
        $translations = []
            + $loader->getTranslations($locale)
            + $defaults->getTranslations($locale);

        ksort($translations);

        // Dump
        $flags = 0
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
            | JSON_PRETTY_PRINT;
        $json  = json_encode($translations, $flags);

        $this->getOutput()->writeln($json);

        // Return
        return self::SUCCESS;
    }
}
