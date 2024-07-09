<?php

namespace App\Http\Middleware;

use App\Services\ContestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessDuringContest
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
        if ($this->contestService->inContest) {
            $newRoute = 'contest.' . Route::currentRouteName();
            /* Fear to remove this code.
            if (Route::has($newRoute))
                return redirect()->route($newRoute, $request->route()->parameters())->withInput($request->all());
            else
                return redirect()->route('home');
            */
            // If some equivalent route is not found, redirect to home.
            if (!Route::has($newRoute))
                return redirect()->route('home');
        }
        return $next($request);
    }
}
