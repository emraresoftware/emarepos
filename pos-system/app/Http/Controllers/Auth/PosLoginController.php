<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosLoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectAfterAuthentication(request());
        }
        return view('pos.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            session(['tenant_id' => $user->tenant_id]);
            session(['branch_id' => $user->branch_id]);
            session()->forget('terminal_id');

            return $this->redirectAfterAuthentication($request);
        }

        return back()->withErrors([
            'email' => 'Giriş bilgileri hatalı.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pos.login');
    }

    public function showTerminalSelect(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('pos.login');
        }

        $terminals = $this->activeTerminalsForAuthenticatedUser();

        if ($terminals->isEmpty()) {
            session(['terminal_id' => null]);
            return redirect()->route('pos.dashboard');
        }

        if ($terminals->count() === 1) {
            session(['terminal_id' => $terminals->first()->id]);
            return redirect()->route('pos.dashboard');
        }

        return view('pos.auth.terminal-select', compact('terminals'));
    }

    public function selectTerminal(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('pos.login');
        }

        $data = $request->validate([
            'terminal_id' => 'required|integer',
        ]);

        $terminalId = $this->activeTerminalsForAuthenticatedUser()
            ->where('id', (int) $data['terminal_id'])
            ->value('id');

        if (! $terminalId) {
            return back()->withErrors([
                'terminal_id' => 'Geçerli bir terminal seçmelisiniz.',
            ]);
        }

        session(['terminal_id' => $terminalId]);

        return redirect()->route('pos.dashboard');
    }

    private function redirectAfterAuthentication(Request $request)
    {
        $terminals = $this->activeTerminalsForAuthenticatedUser();

        if ($terminals->isEmpty()) {
            session(['terminal_id' => null]);
            return redirect()->intended(route('pos.dashboard'));
        }

        if ($terminals->count() === 1) {
            session(['terminal_id' => $terminals->first()->id]);
            return redirect()->intended(route('pos.dashboard'));
        }

        return redirect()->route('pos.terminal.select');
    }

    private function activeTerminalsForAuthenticatedUser()
    {
        $user = Auth::user();

        return PosTerminal::where('tenant_id', $user->tenant_id)
            ->where('branch_id', $user->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description']);
    }
}
