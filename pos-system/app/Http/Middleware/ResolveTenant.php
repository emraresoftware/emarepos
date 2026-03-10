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

            $branch = \App\Models\Branch::find(auth()->user()->branch_id);
            $timezone = $branch?->settings['timezone'] ?? config('app.timezone', 'Europe/Istanbul');
            if ($timezone) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        }
        return $next($request);
    }
}
