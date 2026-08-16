<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    { // نتحقق أن المستخدم مسجل دخول، وأن دوره هو أدمن
        // جلب المستخدم مباشرة من الـ Request (أفضل طريقة والمحرر يفهمها فوراً)
        $user = $request->user();

        // التحقق من وجود المستخدم وأن دوره هو أدمن
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        return response()->json(['message' => __('messages.access.forbidden_admin')], 403);
    }
}
