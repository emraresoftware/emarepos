<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $moduleCode)
    {
        if (!auth()->check()) {
            abort(403);
        }
        
        if (!auth()->user()->hasModule($moduleCode)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bu modül aktif değil.'], 403);
            }
            abort(403, 'Bu modül aktif değil.');
        }
        
        return $next($request);
    }
}
