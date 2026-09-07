<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

use function Laravel\Prompts\table;

class ExtractLangKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:extract {locale=nl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract translation keys to /lang/{locale}.json';

    /**
     * @var list<string>
     */
    private const array DIRECTORIES = [
        'app',
        'app-modules',
        'resources/views',
    ];

    /**
     * @var list<string>
     */
    private const array PATTERNS = [
        '/(?:__|\btrans(?:_choice)?)\(\s*\'([^\']+)\'/',
        '/(?:__|\btrans(?:_choice)?)\(\s*"([^"]+)"/',
        '/@lang\(\s*\'([^\']+)\'/',
        '/@lang\(\s*"([^"]+)"/',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = (string) $this->argument('locale');
        $localePath = lang_path("{$locale}.json");

        /** @var array<string, string> $existing */
        $existing = File::exists($localePath)
            ? json_decode(File::get($localePath), true, 512, JSON_THROW_ON_ERROR) ?? []
            : [];
        $existingKeys = array_keys($existing);

        $searchDirectories = collect(self::DIRECTORIES)
            ->map(fn (string $dir): string => base_path($dir))
            ->all();
        $foundKeys = $this->extractKeys($searchDirectories);

        $addedKeys = collect($foundKeys)->diff($existingKeys);
        $removedKeys = collect($existingKeys)->diff($foundKeys);

        $keys = collect($foundKeys)
            ->mapWithKeys(fn (string $key): array => [$key => $existing[$key] ?? ''])
            ->sortKeys()
            ->all();

        File::ensureDirectoryExists(dirname($localePath));
        File::put($localePath, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

        table(
            ['Found', 'Added', 'Removed'],
            [[
                (string) count($foundKeys),
                (string) $addedKeys->count(),
                (string) $removedKeys->count(),
            ]],
        );

        $this->components->info(sprintf('Translations written to [%s] successfully.', $localePath));

        return self::SUCCESS;
    }

    /**
     * @param  list<string> $directories
     * @return list<string>
     */
    private function extractKeys(array $directories): array
    {
        return collect($directories)
            ->filter(fn (string $dir): bool => File::isDirectory($dir))
            ->flatMap(fn (string $dir): array => File::allFiles($dir))
            ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
            ->flatMap(fn (SplFileInfo $file): array => $this->extractKeysFromFile($file->getPathname()))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function extractKeysFromFile(string $path): array
    {
        $contents = Str::of(File::get($path));

        return collect(self::PATTERNS)
            ->flatMap(fn (string $pattern): array => $contents->matchAll($pattern)->all())
            ->reject(fn (string $key): bool => str_contains($key, '::'))
            ->values()
            ->all();
    }
}
