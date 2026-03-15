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

            $terminals = \App\Models\PosTerminal::where('tenant_id', auth()->user()->tenant_id)
                ->where('branch_id', auth()->user()->branch_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id']);

            if ($terminals->count() === 1) {
                session(['terminal_id' => $terminals->first()->id]);
            } elseif ($terminals->count() > 1) {
                $terminalId = session('terminal_id');
                $validTerminal = $terminalId
                    ? $terminals->contains('id', (int) $terminalId)
                    : false;

                if (! $validTerminal && ! $request->routeIs('pos.terminal.select', 'pos.terminal.select.store')) {
                    return redirect()->route('pos.terminal.select');
                }
            } else {
                session(['terminal_id' => null]);
            }

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
