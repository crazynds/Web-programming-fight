<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class VJudgeService
{
    private $script = '/var/scripts/vjudge.py';

    private $cookies;

    private $username;

    private $password;

    public function __construct()
    {
        $this->username = config('services.vjudge.username');
        $this->password = config('services.vjudge.password');
        $this->cookies = storage_path('app/cookies.pkl');
    }

    private function getLoginTags()
    {
        $username = escapeshellarg($this->username);
        $password = escapeshellarg($this->password);
        $cookies = escapeshellarg($this->cookies);

        return "--cookies $cookies --username $username --password $password";
    }

    public function isEnabled()
    {
        return ! empty($this->username) && ! empty($this->password) && false;
    }

    public function avaliableJudges()
    {
        exec("python3 $this->script oj 2>&1", $output, $retval);
        if ($retval != 0) {
            dd($output);
            abort(401);
        }

        return array_intersect([
            'CodeForces',
        ], json_decode(implode('', $output)));
    }

    public function searchProblems($oj, $title, $page, $perPage = 40)
    {
        $ttl = empty($title ?? '') ? 60 * 60 : 60 * 10;
        $key = empty($title ?? '') ? "vjudge:searchProblems:$oj:$page" : "vjudge:searchProblems:$oj:$page:$title";

        $problems = Cache::remember($key, $ttl, function () use ($oj, $title, $page, $perPage) {
            $login = $this->getLoginTags();
            $oj = escapeshellarg($oj);
            $title = escapeshellarg($title);
            $page = escapeshellarg($page);
            $perPage = escapeshellarg($perPage);
            exec("python3 $this->script problems $login --oj $oj --title $title --page $page --length $perPage", $output, $retval);
            if ($retval != 0) {
                abort(401);
            }

            return json_decode(implode('', $output));
        });

        return collect($problems->data);
    }

    public function testLogin()
    {
        $login = $this->getLoginTags();
        exec("python3 $this->script testLogin $login 2>&1", $output, $retval);
        dd($output, $retval);
    }
}
