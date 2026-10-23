<?php

namespace App\Console\Commands;

use App\Documents\BrowserDataImporter;
use App\Models\Bill;
use App\Models\Item;
use App\Models\Order;
use App\Models\Route;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MigrateBakehouseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bakehouse:migrate-data
        {file? : Optional path to a browser localStorage backup JSON file}
        {--no-seed : Skip seeding default routes, shops, and items from data/seed.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely migrate hardcoded seed data and browser localStorage backup data into the SQL database without duplicates.';

    /**
     * Execute the console command.
     */
    public function handle(BrowserDataImporter $importer, DatabaseSeeder $seeder): int
    {
        if (! $this->option('no-seed')) {
            $this->info('Seeding default routes, items, and shops from data/seed.json...');
            $seeder->run();
        }

        $file = $this->argument('file');

        if (! filled($file)) {
            $this->table(
                ['Table', 'Records in SQL Database'],
                [
                    ['routes', Route::query()->count()],
                    ['shops', Shop::query()->count()],
                    ['items', Item::query()->count()],
                    ['shop_items', DB::table('shop_items')->count()],
                    ['users', User::query()->count()],
                    ['orders', Order::query()->count()],
                    ['bills', Bill::query()->count()],
                ],
            );

            $this->info('Default bakery data is synced in the SQL database.');

            return self::SUCCESS;
        }

        $path = is_file((string) $file) ? (string) $file : base_path((string) $file);

        if (! is_file($path)) {
            $this->error("Backup file not found at [{$file}].");

            return self::FAILURE;
        }

        try {
            $raw = file_get_contents($path);
            $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->error('Invalid JSON backup file: '.$exception->getMessage());

            return self::FAILURE;
        }

        $payload = is_array($decoded['data'] ?? null) ? $decoded['data'] : (is_array($decoded) ? $decoded : null);

        if (! is_array($payload)) {
            $this->error('Backup JSON does not contain a valid data object.');

            return self::FAILURE;
        }

        Storage::disk('local')->put(
            'imports/'.now()->format('Y-m-d_His').'_'.Str::random(6).'.json',
            (string) $raw,
        );

        $actor = User::query()->where('is_owner', true)->first()
            ?? User::query()->first()
            ?? new User(['id' => null, 'is_owner' => true]);

        $result = $importer->import($payload, $actor);

        $rows = [];
        foreach ($result['verification']['checks'] as $section => $check) {
            $rows[] = [$section, $check['expected'], $check['found'], $check['expected'] === $check['found'] ? 'OK' : 'MISMATCH'];
        }

        $this->table(['Section', 'Expected', 'Found in SQL', 'Status'], $rows);

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        if (! $result['verification']['ok']) {
            $this->error('Some records did not pass verification. Do not delete browser localStorage data yet.');

            return self::FAILURE;
        }

        $this->info('All records migrated and verified in the SQL database.');

        return self::SUCCESS;
    }
}
