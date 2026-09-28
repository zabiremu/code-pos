<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Wipes and reseeds the LIVE DEMO database: demo admin / manager / cashier
 * plus the sample catalog. Scheduled hourly in routes/console.php.
 *
 * Refuses to run unless DEMO_MODE=true, and there is deliberately no flag
 * to override that: on a buyer's shop this command would erase every sale.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe and reseed the live demo (only when DEMO_MODE=true)';

    public function handle(): int
    {
        if (! config('app.demo_mode')) {
            $this->error('Refusing to run: DEMO_MODE is not true. This command erases all data and is only for the public live demo.');

            return self::FAILURE;
        }

        $this->info('Resetting the live demo...');

        $result = Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        if ($result !== self::SUCCESS) {
            $this->error(trim(Artisan::output()) ?: 'migrate:fresh failed.');

            return self::FAILURE;
        }

        // Photos visitors uploaded since the last reset.
        $uploads = public_path('uploads');
        foreach (File::isDirectory($uploads) ? File::files($uploads) : [] as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }

        $this->info('Done. Logins: admin@example.com, manager@example.com, cashier@example.com (password: password).');

        return self::SUCCESS;
    }
}
