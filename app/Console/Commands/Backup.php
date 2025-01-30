<?php

namespace App\Console\Commands;

use App\Jobs\BackupJob;
use Illuminate\Console\Command;

class Backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate backup file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        BackupJob::dispatch();
        $this->info('Backup added to queue.');
    }
}
