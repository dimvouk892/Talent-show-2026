<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Απαιτείται πρόσβαση διαχειριστή.'], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Απαιτείται πρόσβαση διαχειριστή.');
        }

        return $next($request);
    }
}
