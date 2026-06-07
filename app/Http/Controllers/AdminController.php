<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRequest;
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



    public function index()
    {
        $requests = VenueRequest::with('owner:id,name,email')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $requests->count(),
            'data' => $requests
        ], 200);
    }

    /**
     * 2. الموافقة على الطلب ونقل البيانات والصور المتعددة للصالات الفعالة
     */
    public function approve($id)
    {
        // جلب الطلب المعلق
        $venueRequest = VenueRequest::where('id', $id)->where('status', 'pending')->firstOrFail();

        // نقل البيانات أو تحديثها في جدول الصالات الأساسي (بناءً على owner_id لضمان صالة واحدة لكل مالك)
        $venue = Venue::updateOrCreate(
            ['owner_id' => $venueRequest->owner_id],
            [
                'name' => $venueRequest->name,
                'address' => $venueRequest->address,
                'capacity' => $venueRequest->capacity,
                'price' => $venueRequest->price,
                'description' => $venueRequest->description,
                'cover_image' => $venueRequest->cover_image,
                'images' => $venueRequest->images, // نفل مصفوفة الصور الإضافية التي رفعتها أنت بنجاح
            ]
        );

        // تحديث حالة الطلب وربطه بالصالة الفعالة
        $venueRequest->update([
            'status' => 'approved',
            'venue_id' => $venue->id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت الموافقة على الطلب بنجاح، ونُقلت البيانات والصور إلى قائمة الصالات الفعالة.',
            'data' => $venue
        ], 200);
    }

    /**
     * 3. رفض الطلب مع تسجيل الملاحظات أو السبب لمالك الصالة
     */
    public function reject(Request $request, $id)
    {
        // التحقق من أن سبب الرفض مكتوب
        $request->validate([
            'admin_notes' => 'required|string|max:1000'
        ]);

        // جلب الطلب المعلق
        $venueRequest = VenueRequest::where('id', $id)->where('status', 'pending')->firstOrFail();

        // تحديث حالة الطلب لـ مرفوض وإضافة السبب
        $venueRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفض الطلب بنجاح، وإرسال الملاحظات إلى صاحب الصالة.',
            'data' => [
                'request_id' => $venueRequest->id,
                'status' => $venueRequest->status,
                'admin_notes' => $venueRequest->admin_notes
            ]
        ], 200);
    }
}
