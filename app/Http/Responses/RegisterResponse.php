<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response after user registration.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        $home = $request->user()->isAdmin()
            ? route('dashboard')
            : route('home');

        return redirect()->intended($home);
    }
}
