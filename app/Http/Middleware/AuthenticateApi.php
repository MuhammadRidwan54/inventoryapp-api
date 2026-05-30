<?php
// app/Http/Middleware/AuthenticateApi.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class AuthenticateApi extends BaseAuthenticate
{
    protected function redirectTo(Request $request): ?string
    {
        // Untuk API, return null dan akan otomatis response 401
        return null;
    }
}