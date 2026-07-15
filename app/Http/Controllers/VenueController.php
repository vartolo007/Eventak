<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('venues', 'public');
        }

        $imagesPath = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagesPath[] = $image->store('venues', 'public');
            }
        }

        $venueRequest = VenueRequest::create([
            'owner_id' => $user->id,
            'venue_id' => null,
            'type' => 'create',
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'price' => $validated['price'],
            'address' => $validated['address'],
            'description' => $validated['description'] ?? null,
            'cover_image' => $coverImagePath,
            'images' => !empty($imagesPath) ? $imagesPath : null,
            'status' => 'pending',
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

        if ($venue->owner_id !== $user->id) {
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $coverImagePath = $venue->cover_image;
        if ($request->hasFile('cover_image')) {
            if ($venue->cover_image) {
                Storage::disk('public')->delete($venue->cover_image);
            }
            $coverImagePath = $request->file('cover_image')->store('venues', 'public');
        }

        $imagesPath = $venue->images ?? [];
        if ($request->hasFile('images')) {
            $imagesPath = [];
            foreach ($request->file('images') as $image) {
                $imagesPath[] = $image->store('venues', 'public');
            }
        }

        $venueRequest = VenueRequest::create([
            'owner_id' => $user->id,
            'venue_id' => $venue->id,
            'type' => 'update',
            'name' => $validated['name'] ?? $venue->name,
            'capacity' => $validated['capacity'] ?? $venue->capacity,
            'price' => $validated['price'] ?? $venue->price,
            'address' => $validated['address'] ?? $venue->address,
            'description' => $validated['description'] ?? $venue->description,
            'cover_image' => $coverImagePath,
            'images' => !empty($imagesPath) ? $imagesPath : null,
            'status' => 'pending',
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

        if ($venue->owner_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية حذف هذه الصالة'
            ], 403);
        }

        // عند الحذف، ننشئ طلب صريح نوعه delete، ونبقي بقية الحقول فارغة أو نأخذ الأساسية فقط
        $venueRequest = VenueRequest::create([
            'owner_id' => $user->id,
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
        $query = Venue::where('status', 'active');

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
