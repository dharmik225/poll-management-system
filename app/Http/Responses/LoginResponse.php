<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $home = $request->user()->isAdmin()
            ? route('dashboard')
            : route('home');

        return redirect()->intended($home);
    }
}
