<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\I18n;
use App\Services\I18n\Storages\Spreadsheet;
use App\Utils\Console\WithOptions;
use App\Utils\Console\WithResult;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ep:i18n-locale-import')]
class LocaleImport extends Command {
    use WithOptions;
    use WithResult;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:i18n-locale-import
        {file : The path to excel file with data}
        {--l|locale= : Locale to import (case sensitive)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = 'Import locale translations.';

    public function __invoke(Application $app, I18n $i18n): int {
        $default = $app->getLocale();
        $locale  = $this->getStringOption('locale', $default);
        $file    = $this->getSpreadsheetArgument('file');
        $strings = (new Spreadsheet($file))->load();
        $result  = $i18n->setTranslations($locale, $strings);

        return $this->result($result);
    }
}
