<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminRegisterRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminRegisterController extends Controller
{
    public function __construct(private UserService $userService) {}

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
    public function store(AdminRegisterRequest $request): RedirectResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
