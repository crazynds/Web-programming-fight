<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupJob implements ShouldQueue, ShouldBeUnique
{   
    use Dispatchable, InteractsWithQueue, Queueable;
    

    public $timeout = 60 * 60 * 2;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('high');
    }

    public function uniqueId(): string
    {
        return 'backup';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->log('Starting backup');
        $backupPath = storage_path('backup');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }
        $backupFile = $backupPath.'/last_backup.zip';
        if(Storage::exists('backup.zip')){
            Storage::delete('backup.zip');
        }

        $zip = new ZipArchive;

        if ($zip->open($backupFile, ZipArchive::CREATE) === true) {
            $dumpFile = $backupPath . '/database.sql';

            $this->generateSqlDump($dumpFile);

            if (file_exists($dumpFile)) {
                $zip->addFile($dumpFile, 'database.sql');
                $this->log('Backup - SQL stored');
            }
            
            /** @var File $file */
            foreach (File::query()->lazy() as $file) {
                if(is_null($file->content)){
                    $tempFile = tempnam(sys_get_temp_dir(), 's3_');
                    file_put_contents($tempFile,$file->get());
                    $zip->addFile($tempFile, 's3/' . $file->path);
                }
            }
            $this->log('Closing backup');
            $zip->close();
            $this->log('Backup ended');
            Storage::put('backup.zip', fopen($backupFile, 'r+'));
            unlink($backupFile);
            unlink($dumpFile);
        }else{
            $this->log('Backup failed');
        }
    }
    private function generateSqlDump(string $dumpFile)
    {   
        $database = config('database.connections.mysql.database');
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --port=%s --no-tablespaces --no-data --skip-triggers %s > %s',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.port')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($dumpFile)
        ); 
        $this->log('Backup - '.$command);
        system($command, $output);
        $command = sprintf(
            "mysqldump --host=%s --user=%s --password=%s --port=%s --no-tablespaces --no-create-info --ignore-table=$database.pulse_aggregates --ignore-table=$database.jobs --ignore-table=$database.pulse_entries --ignore-table=$database.pulse_values --ignore-table=$database.failed_jobs %s >> %s",
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.port')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($dumpFile)
        );
        $this->log('Backup - '.$command);
        system($command, $output);
    }
    private function log(string $text){
        dump($text);
    }
}
