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
            'images' => 'nullable|array', // 👈 التأكد أنها مصفوفة
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048', // 👈 التحقق من كل صورة داخلها
        ]);

        $user = $request->user();

        // 2. فحص ما إذا كان لصاحب الصالة صالة حقيقية مسجلة مسبقاً في النظام
        $existingVenue = Venue::where('owner_id', $user->id)->first();

        // 3. منطق معالجة صورة الغلاف المقترحة
        if ($request->hasFile('cover_image')) {
            // تخزين الصورة الجديدة في مجلد مؤقت للطلبات بانتظار موافقة الأدمن
            $validatedData['cover_image'] = $request->file('cover_image')->store('venues/requests', 'public');
        }

        // 💡 3. الإضافة الجديدة: معالجة الصور الإضافية المتعددة
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                // تخزين كل صورة في مجلد خاص وتوليد اسم فريد لها
                $path = $image->store('venues/gallery', 'public');
                $uploadedImages[] = $path; // حفظ المسار في المصفوفة
            }
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
            'images' => !empty($uploadedImages) ? $uploadedImages : ($existingVenue ? $existingVenue->images : null),
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
    /**
     * محرك البحث والفلترة للصالات بناءً على السعر، القدرة الاستيعابية، والموقع
     */
    public function search(Request $request)
    {
        // ابدأ بالاستعلام الأساسي للصالات
        $query = Venue::query();

        // 1. الفلترة حسب الاسم أو العنوان (البحث النصي)
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        // 2. الفلترة حسب السعر الأقصى
        $query->when($request->filled('price_max'), function ($q) use ($request) {
            return $q->where('price', '<=', $request->price_max);
        });

        // 3. الفلترة حسب الحد الأدنى للقدرة الاستيعابية (عدد الضيوف)
        $query->when($request->filled('capacity'), function ($q) use ($request) {
            return $q->where('capacity', '>=', $request->capacity);
        });

        // جلب النتائج مع ترتيبها من الأحدث للأقدم
        $venues = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'count' => $venues->count(),
            'data' => $venues
        ], 200);
    }
}
