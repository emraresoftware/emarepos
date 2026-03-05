<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            session(['tenant_id' => auth()->user()->tenant_id]);
            session(['branch_id' => auth()->user()->branch_id]);
        }
        return $next($request);
    }
}
