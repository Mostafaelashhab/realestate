<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * لوحة الإدارة متاحة فقط لإيميل المشرف (config enr.admin_email).
 * أي حد تاني — حتى لو مسجّل دخول — بياخد 404 عشان مايعرفش إن الصفحة موجودة.
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = strtolower(trim((string) config('enr.admin_email')));
        $user = strtolower(trim((string) optional($request->user())->email));

        abort_unless($admin !== '' && $user !== '' && hash_equals($admin, $user), 404);

        return $next($request);
    }
}
