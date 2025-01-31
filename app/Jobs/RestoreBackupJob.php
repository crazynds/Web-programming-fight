<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 60 * 60 * 2;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Ativar modo de manutenção
            //Artisan::call('down');

            //BackupJob::dispatchSync();

            $backupPath = storage_path('backup');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0777, true);
            }
            // file_put_contents($backupPath.'/restore_backup.zip', Storage::readStream('restore_backup.zip'));

            // $zip = new ZipArchive;
            // if ($zip->open($backupPath.'/restore_backup.zip') !== true) {
            //     $this->fail('Could not open backup file.');
            // }

            $extractPath = storage_path('backup/extract');
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            // $zip->extractTo($extractPath);
            // $zip->close();
            
            // unlink($backupPath.'/restore_backup.zip');

            // Restaurar o banco de dados
            $this->restoreDatabase("$extractPath/database.sql");

            // Restaurar os arquivos para o S3
            $this->restoreS3Files("$extractPath/s3");
            //rmdir($extractPath);
        } catch(Exception $ex){
            dump($ex);
            throw $ex;
        } finally {
            // Desativar modo de manutenção
            Artisan::call('up');   
        }   
        
    }

    
    private function restoreDatabase($sqlFile)
    {
        if (!file_exists($sqlFile)) {
            return;
        }
        DB::beginTransaction();
        // Apaga todas as tabelas antes de restaurar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = DB::select("SHOW TABLES");
        foreach ($tables as $tableObj) {
            $table = array_values((array) $tableObj)[0];
            DB::statement("DROP TABLE IF EXISTS `$table`;");
            dump("DROP TABLE IF EXISTS `$table`;");
        }

        // // Ler e executar cada comando do SQL
        $sql = file_get_contents($sqlFile);
        DB::unprepared($sql);
        dump("RODOU O IMPORT DB!!");
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::commit();
    }

    private function restoreS3Files($s3Path)
    {
        if (!file_exists($s3Path)) {
            return;
        }

        foreach(Storage::allFiles() as $file) {
            if($file == 'restore_backup.zip') continue;
            if($file == 'backup.zip') continue;
            Storage::delete($file);
        }

        // $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($s3Path));
        // foreach ($files as $file) {
        //     if (!$file->isFile()) continue;

        //     $relativePath = str_replace("$s3Path/", '', $file->getPathname());
        //     Storage::put($relativePath, file_get_contents($file->getPathname()));
        // }
    }

}
