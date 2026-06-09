<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    // 1. GET /api/user/profile -> عرض بيانات المستخدم الحالي
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null
            ]
        ]);
    }

    // 2. PUT /api/user/profile -> تحديث البيانات الشخصية
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات الحساب الشخصي بنجاح.',
            'data' => $user
        ]);
    }

    // 3. POST /api/user/avatar -> رفع أو تحديث الصورة الشخصية
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // حد أقصى 2 ميجا
        ]);

        $user = $request->user();

        // حذف الصورة القديمة إذا كانت موجودة لمنع تراكم الملفات
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // حفظ الصورة الجديدة في مجلد avatars داخل الـ public storage
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفع الصورة الشخصية بنجاح.',
            'avatar_url' => asset('storage/' . $path)
        ]);
    }

    // 4. DELETE /api/user/avatar -> حذف الصورة الشخصية العائدة للمستخدم
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الصورة الشخصية بنجاح والعوّدة للصورة الافتراضية.'
        ]);
    }



    // public function index()
    // {

    //     $users = [
    //         ['id' => 1, 'name' => "ahmad"],
    //         ['id' => 2, 'name' => "ali"],
    //         ['id' => 3, 'name' => "sara"],
    //     ];
    //     // foreach ($users as $user) {
    //     //     echo "User ID: " . $user['id'] . ", Name: " . $user['name'] . "\n";
    //     // }

    //     return response()->json(["name" => "ahmad"]);
    // }



    // public function checkuser($id)
    // {
    //     if ($id > 10) {
    //         return response()->json([' message' => '  access  denied ']);
    //     } else {
    //         return response()->json([' message' => '  access  accepted ']);
    //     }

    //     return response()->json($id);
    // }



    // public function the_names()
    // {
    //     $names = ["ahmed", "ali", "rawan", "fatima"];
    //     $capitalized = [];

    //     foreach ($names as $name) {
    //         $upperName = '';

    //         for ($i = 0; $i < strlen($name); $i++) {
    //             $char = $name[$i];
    //             $ascii = ord($char);


    //             if ($ascii >= 97 && $ascii <= 122) {
    //                 $upperName .= chr($ascii - 32);
    //             } else {
    //                 $upperName .= $char;
    //             }
    //         }
    //         $capitalized[] = $upperName;
    //     }

    //     return response()->json($capitalized);
    // }
}
