<?php

namespace App\Jobs;

use App\Models\File;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sysatom\BloomFilter;

class ClearUnusedFiles implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        // 16 MB of bloom filter
        // 5 hashes
        $filter = new BloomFilter(8 * 1024 * 1024 * 16, 5);
        // randoness to every run hit different
        $randPrefix = Str::random(12);
        foreach (File::whereNull('content')->lazy() as $file) {
            $path = $file->path;
            $filter->add($randPrefix.$path);
        }
        $toDelete = [];
        $storage = Storage::disk(config('filesystems.default'));
        foreach ($storage->allFiles() as $file) {

            if (! $filter->lookup($randPrefix.$file)) {
                $toDelete[] = $file;
                $size = $storage->size($file);
                Log::channel('leakedfiles')->info(
                    sprintf('%s -- %d Kb', $file, $size / 1024)
                );
            }
        }

        // TODO: quando essa parte estiver 100%, remover esse trecho de código.
        foreach ($toDelete as $path) {
            dump($path);
            $file = File::where('path', $path)->first();
            if ($file) {
                Log::channel('leakedfiles')->info(
                    sprintf('Falso positivo: %s', $file)
                );
            } else {
                $storage->delete($path);
            }
        }

        $recovered = 0;
        foreach (File::lazy() as $file) {
            try {
                $file->delete();
                $recovered += $file->size;
                Log::channel('leakedfiles')->info(
                    sprintf('Deletei um file inutil: %s', $file)
                );
            } catch (Exception $exection) {
            }
        }
        if ($recovered > 0) {
            Log::channel('leakedfiles')->warning(
                sprintf('Recuperado em bytes de arquivos inuteis', $recovered)
            );
        }
    }
}
