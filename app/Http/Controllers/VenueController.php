<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\VenueRequest;

class VenueController extends Controller
{
   /**
     * إرسال طلب إنشاء أو تعديل بيانات الصالة إلى الأدمن للمراجعة
     */
    public function updateOrCreate(Request $request)
    {
        // 1. التحقق من المدخلات القادمة من صاحب الصالة
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // 2. فحص ما إذا كان لصاحب الصالة صالة حقيقية مسجلة مسبقاً في النظام
        $existingVenue = Venue::where('owner_id', $user->id)->first();

        // 3. منطق معالجة صورة الغلاف المقترحة
        if ($request->hasFile('cover_image')) {
            // تخزين الصورة الجديدة في مجلد مؤقت للطلبات بانتظار موافقة الأدمن
            $validatedData['cover_image'] = $request->file('cover_image')->store('venues/requests', 'public');
        }

        // 4. إنشاء سجل "طلب تعديل/إضافة" جديد للأدمن
        // هذا السجل يذهب لجدول الـ VenueRequest وليس لجدول الصالات الأساسي
        $venueRequest = VenueRequest::create([
            'owner_id' => $user->id,
            'venue_id' => $existingVenue ? $existingVenue->id : null, // إذا كانت الصالة موجودة، نربط الطلب بها كتعديل، وإذا كانت جديدة يترك null كطلب إنشاء
            'name' => $validatedData['name'],
            'address' => $validatedData['address'],
            'capacity' => $validatedData['capacity'],
            'price' => $validatedData['price'],
            'description' => $validatedData['description'] ?? null,
            'cover_image' => $validatedData['cover_image'] ?? ($existingVenue ? $existingVenue->cover_image : null),
            'status' => 'pending', // حالة الطلب الافتراضية معلق بانتظار الأدمن
        ]);

        // 5. إرجاع الاستجابة للفرونت إند لإعلام صاحب الصالة
        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال البيانات بنجاح إلى الأدمن مراجعتها واعتمادها.',
            'data' => [
                'request_id' => $venueRequest->id,
                'status' => $venueRequest->status,

                'cover_image_url' => $venueRequest->cover_image ? asset('storage/' . $venueRequest->cover_image) : null
            ]
        ], 202);
    }
}
