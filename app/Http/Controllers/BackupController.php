<?php

namespace App\Http\Controllers;

use App\Jobs\BackupJob;
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
        $backupPath = storage_path('storage/backup/temp_backup.zip');
        $backupFile->move(storage_path('storage/backup'), 'temp_backup.zip');

        defer(function () use ($backupPath) {
            unlink($backupPath);
        });

        // Extrair o ZIP
        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            return response()->json(['error' => 'Não foi possível abrir o arquivo ZIP'], 400);
        }

        $extractPath = storage_path('storage/backup_extract');
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0777, true);
        }
        $zip->extractTo($extractPath);
        $zip->close();

        // Restaurar o banco de dados
        $sqlFile = "$extractPath/database.sql";
        if (file_exists($sqlFile)) {
            $this->restoreDatabase($sqlFile);
        }

        // Restaurar os arquivos para o S3
        $this->restoreS3Files($extractPath);

        // Remover arquivos temporários
        unlink($backupPath);
        $this->deleteFolder($extractPath);

        return response()->json(['message' => 'Backup restaurado com sucesso!']);
    }

    private function restoreDatabase($sqlFile)
    {
        // Apaga todas as tabelas antes de restaurar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = DB::select("SHOW TABLES");
        foreach ($tables as $tableObj) {
            $table = array_values((array) $tableObj)[0];
            DB::statement("DROP TABLE IF EXISTS `$table`;");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // // Ler e executar cada comando do SQL
        // $sql = file_get_contents($sqlFile);
        // DB::unprepared($sql);
    }

    private function restoreS3Files($extractPath)
    {
        $s3Path = "$extractPath/s3";
        if (!file_exists($s3Path)) {
            return;
        }

        foreach(Storage::allFiles() as $file) {
            Storage::delete($file);
        }

        // $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($s3Path));
        // foreach ($files as $file) {
        //     if (!$file->isFile()) continue;

        //     $relativePath = str_replace("$s3Path/", '', $file->getPathname());
        //     Storage::put($relativePath, file_get_contents($file->getPathname()));
        // }
    }


    public function backupNow()
    {
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

}
