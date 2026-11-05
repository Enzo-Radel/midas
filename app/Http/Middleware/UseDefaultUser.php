<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica todas as requisições com o usuário padrão enquanto o sistema não tem login.
 */
class UseDefaultUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            Auth::setUser(User::firstOrCreate(
                ['email' => config('midas.owner.email')],
                ['name' => config('midas.owner.name'), 'password' => Str::random(40)],
            ));
        }

        return $next($request);
    }
}
