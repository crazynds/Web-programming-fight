<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class DockerBuildAndPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:build-push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a docker image and push to docker hub.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        $command = 'docker build --file ./docker/prod/Dockerfile . -t crazynds/web-programming-fight:latest && docker push crazynds/web-programming-fight --all-tags';
        $path = __DIR__ . '/../../..';
        $this->info('Path: ' . $path);
        Process::path($path)->forever()->run($command, function (string $type, string $output) {
            echo $output;
        });
    }
}
