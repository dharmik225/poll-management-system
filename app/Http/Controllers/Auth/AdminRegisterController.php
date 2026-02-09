<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminRegisterController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Show the admin registration form.
     */
    public function create(): View
    {
        return view('livewire.auth.admin-register');
    }

    /**
     * Handle admin registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => true,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
