<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa funcionalidades';

    /**
     * Execute the console command.
     */
    public function handle()
    {   
        $this->info('Testing cache:');
        $this->line('old: '.Cache::get('bar'));
        Cache::put('bar', 'baz', 20);
        $this->line('new: '.Cache::get('bar'));
        $this->newLine();

        
        $this->info('Testing FileSystem:');
        $this->line(Storage::get('test.txt') ?? 'não encontrado');
        Storage::put('test.txt', 'ola mundo');
        $this->line(Storage::get('test.txt') ?? 'não encontrado');
        Storage::delete('test.txt');
        $this->line(Storage::get('test.txt') ?? 'não encontrado');
    }
}
