<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function addVendor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'role' => 'required|in:vendor,venue_owner' // تحديد نوع الحساب
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            // لا يوجد حقل كلمة مرور هنا، لأن المورد سيدخل عبر الـ OTP حصراً!
        ]);

        return response()->json([
            'message' => 'تم إضافة المورد بنجاح، يمكنه الآن الدخول عبر الـ OTP',
            'user' => $user
        ]);
    }
}
