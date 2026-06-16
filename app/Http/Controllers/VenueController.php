<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\VenueRequest;
use App\Models\User;
use App\Notifications\NewVenueRequestNotification;

class VenueController extends Controller
{
    /**
     * 1. دالة إضافة صالة جديدة (تخزن الحقول الصريحة في جدول الـ requests)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'address' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // هنا نستخدم الحقول الصريحة داخل جدول الـ requests كما هي في قاعدة بياناتك
        $venueRequest = VenueRequest::create([
            'user_id' => $user->id,
            'venue_id' => null, // صالة جديدة
            'type' => 'create',
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'price' => $validated['price'],
            'address' => $validated['address'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending' // معلق بانتظار الأدمن
        ]);

        // 🔔 إشعار الأدمن في الداتابيز
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewVenueRequestNotification($venueRequest, 'create'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب إضافة الصالة للأدمن بنجاح وهو قيد المراجعة حالياً.',
            'data' => $venueRequest
        ], 201);
    }

    /**
     * 2. دالة تعديل بيانات صالة (تأخذ الحقول وتضعها في سطر جديد بجدول الـ requests)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $venue = Venue::findOrFail($id); // الصالة الأصلية الحية

        if ($venue->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية تعديل بيانات هذه الصالة'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'address' => 'sometimes|string',
            'description' => 'nullable|string',
        ]);

        // نفتح سطر طلب جديد صريح ونملأه بالبيانات المعدلة القادمة من الـ Request
        // وإذا لم يرسل الفرونت حقل معين، نأخذ قيمته الحالية من الصالة الأصلية كرمال ما ينزل السجل ناقص
        $venueRequest = VenueRequest::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id, // ربط الطلب بالصالة الحية المراد تعديلها
            'type' => 'update',
            'name' => $validated['name'] ?? $venue->name,
            'capacity' => $validated['capacity'] ?? $venue->capacity,
            'price' => $validated['price'] ?? $venue->price,
            'address' => $validated['address'] ?? $venue->address,
            'description' => $validated['description'] ?? $venue->description,
            'status' => 'pending'
        ]);

        // 🔔 إشعار الأدمن
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewVenueRequestNotification($venueRequest, 'update'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب التعديل للأدمن بنجاح وبقيت الصالة القديمة نشطة حتى يوافق.',
            'data' => $venueRequest
        ], 200);
    }

    /**
     * 3. دالة طلب حذف صالة
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $venue = Venue::findOrFail($id);

        if ($venue->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية حذف هذه الصالة'
            ], 403);
        }

        // عند الحذف، ننشئ طلب صريح نوعه delete، ونبقي بقية الحقول فارغة أو نأخذ الأساسية فقط
        $venueRequest = VenueRequest::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'type' => 'delete',
            'name' => $venue->name,
            'capacity' => $venue->capacity,
            'price' => $venue->price,
            'address' => $venue->address,
            'status' => 'pending'
        ]);

        // 🔔 إشعار الأدمن
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new NewVenueRequestNotification($venueRequest, 'delete'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تقديم طلب حذف الصالة للأدمن، وستبقى معروضة حتى يوافق على مسحها.'
        ], 200);
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
