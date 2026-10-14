<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Partner;
use App\Support\PrivateFiles;
use Illuminate\Console\Command;

/**
 * Moves the old contracts and partner documents from the public disk to
 * the private disk. Run it once:  php artisan files:make-private
 */
class MakeSensitiveFilesPrivate extends Command
{
    protected $signature = 'files:make-private';

    protected $description = 'Move contracts and partner documents off the public disk';

    public function handle(): int
    {
        $moved = 0;

        foreach (Contract::pluck('pdf_path') as $path) {
            $moved += PrivateFiles::ensurePrivate($path) ? 1 : 0;
        }
        foreach (Partner::whereNotNull('documents_path')->pluck('documents_path') as $path) {
            $moved += PrivateFiles::ensurePrivate($path) ? 1 : 0;
        }

        $this->info("{$moved} sensitive file(s) are now on the private disk.");

        return self::SUCCESS;
    }
}
