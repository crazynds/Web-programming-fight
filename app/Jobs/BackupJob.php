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
                    $this->log('Backup - File stored: ' . $file->path);
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
        $tables = DB::select("SHOW TABLES"); // Lista todas as tabelas
        $database = env('DB_DATABASE');

        $sql = "-- Backup do banco de dados: $database\n\n";
        
        foreach ($tables as $tableObj) {
            $table = array_values((array) $tableObj)[0];

            // Criação da tabela
            $createTable = DB::select("SHOW CREATE TABLE $table")[0]->{"Create Table"};
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= "$createTable;\n\n";

            // Inserção de dados
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $values = array_map(fn($val) => is_null($val) ? "NULL" : "'" . addslashes($val) . "'", (array) $row);
                $sql .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
            }
            $sql .= "\n";
        }

        file_put_contents($dumpFile, $sql);
    }
    private function log(string $text){
        dump($text);
    }
}
