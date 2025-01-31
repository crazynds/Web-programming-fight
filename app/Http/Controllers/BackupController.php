<?php

namespace App\Http\Controllers;

use App\Jobs\BackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Zip;

class BackupController extends Controller
{

    public function index(){
        return view('pages.backup.index');
    }

    public function start(){
        BackupJob::dispatch()->onQueue('high');
        return response()->json([
            'status' => 200,
            'msg' => 'Backup dispatched!'
        ],200);
    }

    public function download(){
        $backupFile = 'backup.zip';
        if(Storage::exists($backupFile)){
            return Storage::download($backupFile);
        }
        return response()->json([
            'status' => 404,
            'msg' => 'Backup not found!'
        ],404);
    }
    public function upload(Request $request)
    {
        $request->validate([
            'backup' => 'required|file|mimes:zip',
        ]);

        // Salvar o arquivo temporariamente
        $backupFile = $request->file('backup');
        $backupFile->storeAs('/','restore_backup.zip');
        
        RestoreBackupJob::dispatch($backupFile)->onQueue('high');

        return response()->json(['message' => 'Job para restaurar backup agendado!']);
    }

    public function backupNow()
    {
        set_time_limit(0);
        $backupPath = storage_path('backup');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        // Nome do arquivo de backup
        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = Zip::create($backupFile);

        // Dump do banco de dados
        $dumpFile = $backupPath . '/database.sql';

        $this->generateSqlDump($dumpFile);

        if (file_exists($dumpFile)) {
            $zip->add($dumpFile, 'database.sql');
        }
        
        /** @var File $file */
        foreach (File::query()->lazy() as $file) {
            if(is_null($file->content))
                $file->addToZip($zip, 's3/' . $file->path);
        }

        return $zip;
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
        system($command, $output);
    }

}
