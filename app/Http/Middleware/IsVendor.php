<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsVendor
{
    /**
     * معالجة الطلب القادم والتأكد من رتبة المورد
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. التأكد من أن المستخدم مسجل دخول حالياً ولديه توكن صالح
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'يجب تسجيل الدخول أولاً للوصول إلى هذه الصلاحية.'
            ], 401);
        }

        // 2. التحقق من أن رتبة المستخدم في جدول الـ users هي 'vendor'
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مسموح! هذه الصلاحية مخصصة للموردين فقط.'
            ], 403); // كود 403 يعني Forbidden (غير مصرح)
        }

        return $next($request);
    }
}
