<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;

class LoginController extends Controller
{
    /**
     * Handle a login request with phone and password or PIN code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'pin_code' => ['nullable', 'string', 'size:6'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // Must have either password or pin_code
        if (!$request->filled('password') && !$request->filled('pin_code')) {
            throw ValidationException::withMessages([
                'credentials' => ['Le mot de passe ou le code PIN est requis.'],
            ]);
        }

        $phone = SmsService::normalizePhone($request->phone);

        // Find user by phone
        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ['Ces identifiants ne correspondent pas à nos enregistrements.'],
            ]);
        }

        $authenticated = false;

        // Check PIN code authentication
        if ($request->filled('pin_code')) {
            if ($user->canAuthenticateWithPin()) {
                $pinHash = $user->getAttributes()['pin_code'] ?? null;
                if ($pinHash && Hash::check($request->pin_code, $pinHash)) {
                    $authenticated = true;
                }
            }
        }
        // Check password authentication
        elseif ($request->filled('password')) {
            if ($user->canAuthenticateWithPassword() && Hash::check($request->password, $user->password)) {
                $authenticated = true;
            }
        }

        if (!$authenticated) {
            throw ValidationException::withMessages([
                'pin_code' => $request->filled('pin_code')
                    ? ['Le code PIN est incorrect.']
                    : [],
                'password' => $request->filled('password')
                    ? ['Le mot de passe est incorrect.']
                    : [],
            ]);
        }

        // Login the user
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the post-login redirect path.
     */
    protected function redirectPath(): string
    {
        return config('fortify.home', '/dashboard');
    }
}
