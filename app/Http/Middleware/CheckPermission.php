<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        abort_unless($request->user()?->hasPermission($code), 403, 'Anda tidak memiliki hak akses untuk tindakan ini.');

        return $next($request);
    }
}
