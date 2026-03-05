<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            abort(403, 'Yetkilendirme gerekli.');
        }
        
        if (!auth()->user()->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bu işlem için yetkiniz yok.'], 403);
            }
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
        
        return $next($request);
    }
}
