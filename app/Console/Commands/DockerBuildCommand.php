<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DockerBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        exec('docker build --file ./docker/prod/Dockerfile . -t crazynds/web-programming-fight:latest');
    }
}
