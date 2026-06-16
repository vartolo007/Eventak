<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Notifications\NewServiceRequestNotification;

class ServiceController extends Controller
{
    // عرض جميع الخدمات (للضيوف والمسجلين - تظهر النشطة فقط)
    public function index(Request $request)
    {
        // تم التأكيد هنا على جلب 'active' فقط، كرمال ما تظهر الخدمات المعلقة للزبائن
        $query = Service::where('status', 'active')->with('vendor', 'category');

        // فلترة حسب التصنيف
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // بحث حسب الاسم
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // فلترة حسب السعر
        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        $services = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'count' => $services->count(),
            'data' => $services
        ]);
    }

    // عرض تصنيفات الخدمات
    public function categories()
    {
        // جلب التصنيفات مع عد الخدمات النشطة فقط
        $categories = ServiceCategory::withCount(['services' => function ($query) {
            $query->where('status', 'active');
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    // عرض خدمة واحدة (تأكد أنها نشطة أو تخص المورد نفسه)
    public function show($id)
    {
        $service = Service::with('vendor', 'category')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $service
        ]);
    }


}
