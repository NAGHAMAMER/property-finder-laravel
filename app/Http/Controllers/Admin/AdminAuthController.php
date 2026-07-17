<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // A normal-user web session must never block the admin login page.
            $this->logoutCurrentWebSession($request);
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::check() && ! Auth::user()->isAdmin()) {
            $this->logoutCurrentWebSession($request);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'])
                ->onlyInput('email');
        }

        if (! Auth::user()->isAdmin()) {
            $this->logoutCurrentWebSession($request);

            return back()
                ->withErrors(['email' => 'هذا الحساب لا يملك صلاحية الأدمن.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutCurrentWebSession($request);

        return redirect()->route('admin.login');
    }

    private function logoutCurrentWebSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
