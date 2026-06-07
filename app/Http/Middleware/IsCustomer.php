<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // التحقق من أن المستخدم مسجل دخول وأن دوره في النظام هو زبون حصراً
        if ($user && $user->role === 'customer') {
            return $next($request);
        }

        // إرجاع استجابة منع واضحة ومفهومة للفرونت إند في حال كان الدور مختلفاً
        return response()->json([
            'status' => 'error',
            'message' => 'غير مصرح لك بالوصول، هذه الصلاحية مخصصة للزبائن فقط.'
        ], 403);
}
}
