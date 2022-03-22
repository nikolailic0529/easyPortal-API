<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Coverage;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\I18n\Formatter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

use function array_keys;
use function array_map;
use function in_array;
use function max;
use function mb_strtolower;
use function str_pad;

class AssetsCount extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-assets-count
        {--r|reseller= : Reseller ID (required)}
        {--c|coverage= : Coverage ID/Key (required)}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Calculate assets for given Reseller and Coverage.';

    public function __invoke(Client $client, Formatter $formatter): int {
        // Prepare
        $reseller = Reseller::query()->whereKey($this->option('reseller'))->firstOrFail();
        $coverage = $this->option('coverage');
        $coverage = Coverage::query()
            ->where(static function (Builder $builder) use ($coverage): Builder {
                return $builder
                    ->orWhere($builder->getModel()->getKeyName(), $coverage)
                    ->orWhere('key', $coverage);
            })
            ->firstOrFail();

        // Calculate
        $iterator       = $client->getAssetsByResellerId($reseller->getKey());
        $coverageKey    = mb_strtolower($coverage->key);
        $assetsTotal    = 0;
        $assetsCoverage = 0;

        foreach ($iterator as $asset) {
            /** @var ViewAsset $asset */

            // Total
            $assetsTotal++;

            // Coverage
            $coverages = (array) $asset->assetCoverage;
            $coverages = array_map('mb_strtolower', $coverages);

            if (in_array($coverageKey, $coverages, true)) {
                $assetsCoverage++;
            }
        }

        // Show
        $lines  = [
            'Total Assets'  => $assetsTotal,
            $coverage->name => $assetsCoverage,
        ];
        $length = max(array_map('mb_strlen', array_keys($lines)));

        foreach ($lines as $header => $value) {
            $this->line(str_pad(Str::title($header), $length).': '.$formatter->integer($value));
        }

        // Done
        $this->newLine();
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
