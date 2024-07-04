<?php

namespace App\Http\Middleware;

use App\Services\ContestService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessOnlyDuringContest
{

    public function __construct(protected ContestService $contestService)
    {
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->contestService->inContest || !$this->contestService->started) {
            return redirect()->route('home');
        }
        return $next($request);
    }
}
