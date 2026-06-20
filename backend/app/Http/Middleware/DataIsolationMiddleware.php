<?php

namespace App\Http\Middleware;

use App\Services\DataIsolationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DataIsolationMiddleware
{
    public function __construct(protected DataIsolationService $isolationService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $this->isolationService->setUser($user);
        }

        return $next($request);
    }
}
