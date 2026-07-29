<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerEventController;
use App\Http\Controllers\VenueOrderController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\RatingController;

// مسارات غير محمية (يمكن لأي شخص الوصول إليها)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// مسارات محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});


// مسارات الـ OTP للموردين وأصحاب الصالات
Route::post('/auth/send-otp', [OtpAuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [OtpAuthController::class, 'verifyOtp']);


// مسارات استعادة كلمة المرور
Route::post('/auth/forgot-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [ResetPasswordController::class, 'resetPassword']);


// تطبيق حارس التوكن (sanctum) وحارس الإدارة (isAdmin) معاً
Route::middleware(['auth:sanctum', 'isAdmin'])->group(function () {

    Route::post('/admin/add-user', [AdminController::class, 'addUser']);



    Route::get('/admin/services/requests', [AdminController::class, 'serviceRequests']);
    Route::put('/admin/services/{id}/approve', [AdminController::class, 'approveService']);
    Route::put('/admin/services/{id}/reject', [AdminController::class, 'rejectService']);

    Route::get('/admin/venue-requests', [AdminController::class, 'venueRequests']);                    // عرض الطلبات المعلقة
    Route::put('/admin/venue-requests/{id}/approve', [AdminController::class, 'approveVenueRequest']); // موافقة
    Route::put('/admin/venue-requests/{id}/reject', [AdminController::class, 'rejectVenueRequest']);   // رفض

    Route::get('/admin/users', [AdminController::class, 'getAllUsers']);         // عرض إدارة المستخدمين والموردين
    Route::get('/admin/venues/active', [AdminController::class, 'getActiveVenues']); // عرض كل الصالات الفعالة
    Route::get('/admin/events/all', [AdminController::class, 'getAllEvents']);   // تصفح ومراقبة كل الحفلات وفواتيرها

    // مسارات إدارة تصنيفات الخدمات بواسطة الأدمن
    Route::get('/admin/service-categories', [AdminController::class, 'indexServiceCategories']);
    Route::post('/admin/service-categories', [AdminController::class, 'storeServiceCategory']);
    Route::put('/admin/service-categories/{id}', [AdminController::class, 'updateServiceCategory']);
    Route::delete('/admin/service-categories/{id}', [AdminController::class, 'destroyServiceCategory']);
    // أي مسارات إضافية للآدمن مستقبلاً توضع هنا...
});

// 1️⃣ مسارات عامة (متاحة للجميع: ضيوف، زبائن، موردين)
Route::get('/customer/venues', [CustomerEventController::class, 'listVenues']); // عرض الصالات
Route::get('/services', [ServiceController::class, 'index']);             // عرض كافة الخدمات مع الفلترة
Route::get('/services/categories', [ServiceController::class, 'categories']); // عرض تصنيفات الخدمات
Route::get('/services/{id}', [ServiceController::class, 'show']);         // عرض تفاصيل خدمة واحدة
Route::get('/venues/search', [VenueController::class, 'search']);         // البحث والفلترة في الصالات (متاح للضيوف أيضاً)
Route::get('/venues/{venueId}/ratings', [RatingController::class, 'venueRatings']); // ⭐ عرض تقييمات صالة معينة مع المعدل


// حماية المسار بـ Sanctum والـ Middleware الخاص بصاحب الصالة (venue_owner)
Route::middleware(['auth:sanctum', 'isVenueOwner'])->group(function () {

    // مسار تحديث بيانات الصالة
    Route::get('/venue-owner/venues', [VenueController::class, 'myVenues']);     //عرض صالاتي
    Route::post('/venue-owner/venue', [VenueController::class, 'store']);         // إضافة صالة جديدة
    Route::put('/venue-owner/venue/{id}', [VenueController::class, 'update']);     // تعديل صالة معينة
    Route::delete('/venue-owner/venue/{id}', [VenueController::class, 'destroy']); // طلب حذف صالة
    Route::get('/venue-owner/requests', [VenueController::class, 'myRequests']);    //عرض طلباتي

    // مسارات قرارات الحجز الجديدة لصاحب الصالة
    Route::get('/venue-owner/events', [VenueOrderController::class, 'ownerIndex']);
    Route::put('/venue-owner/events/{id}/accept', [VenueOrderController::class, 'accept']);
    Route::put('/venue-owner/events/{id}/reject', [VenueOrderController::class, 'reject']);
    Route::put('/venue-owner/events/{id}/complete', [VenueOrderController::class, 'complete']);
});


// مسارات الزبون
Route::middleware(['auth:sanctum', 'isCustomer'])->group(function () {
    // إنشاء مناسبة جديدة
    Route::post('/customer/events', [CustomerEventController::class, 'store']);

    // عرض مناسباتي
    Route::get('/customer/events', [CustomerEventController::class, 'myRequests']);
    Route::get('/customer/events/{id}', [CustomerEventController::class, 'showRequest']);

    // إلغاء الحجز (مع استرجاع متدرج للمبلغ إذا كان مدفوعاً: +7أيام=100%، 48س-7أيام=50%، أقل=0%)
    Route::put('/customer/events/{id}/cancel', [CustomerEventController::class, 'cancel']);

    // 💳 مسارات الدفع والوصولات المالية للزبون:
    Route::get('/customer/invoices/{invoiceId}/pending', [PaymentController::class, 'showPendingInvoiceDetails']); // لعرض تفاصيل فاتورة بانتظار الدفع
    Route::post('/customer/payments', [PaymentController::class, 'store']);                    // إجراء عملية دفع جديدة
    Route::get('/customer/payments/{id}', [PaymentController::class, 'show']);                 // عرض تفاصيل وصل دفع معين
    Route::get('/customer/invoices/{invoiceId}/payment', [PaymentController::class, 'byInvoice']); // عرض دفع متعلق بفاتورة معينة

    // ⭐ مسارات التقييمات (بعد اكتمال المناسبة)
    Route::post('/customer/events/{eventId}/rating', [RatingController::class, 'store']); // إضافة تقييم لمناسبة مكتملة
    Route::get('/customer/events/{eventId}/rating', [RatingController::class, 'show']);   // عرض تقييمي لمناسبة معينة
});

// مسارات المورد (Vendor Routes)
Route::middleware(['auth:sanctum', 'isVendor'])->group(function () {
    // 🛠️ 1. مسارات إدارة الخدمات الخاصة بالمورد (إضافة، تعديل، طلب حذف)
    Route::post('/vendor/services', [VendorOrderController::class, 'store']);          // إضافة خدمة جديدة
    Route::post('/vendor/services/{id}', [VendorOrderController::class, 'update']);     // تعديل الخدمة (استخدمنا POST لدعم رفع الصور)
    Route::delete('/vendor/services/{id}', [VendorOrderController::class, 'destroy']);  // طلب حذف الخدمة


    // 🚚 إدارة طلبات خدمات المناسبات للمورد:
    Route::get('/vendor/orders', [VendorOrderController::class, 'index']); // عرض الطلبات القادمة للمورد
    Route::put('/vendor/orders/{eventId}/services/{serviceId}/accept', [VendorOrderController::class, 'acceptService']); // قبول الخدمة
    Route::put('/vendor/orders/{eventId}/services/{serviceId}/reject', [VendorOrderController::class, 'rejectService']); // رفض الخدمة

    // راوت استعراض شاشة "خدماتي وباقاتي" للمورد:
    Route::get('/vendor/services', [VendorOrderController::class, 'myServices']);
});


// مسارات محمية بـ التوكن للمستخدمين المسجلين (أي دور كان)
Route::middleware('auth:sanctum')->group(function () {

    // 🔏 مسارات الـ User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [UserController::class, 'deleteAvatar']);

    // 🔔 مسارات نظام الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index']);       //عرض كافة الإشعارات (المقروءة وغير المقروءة)
    Route::get('/notifications/unread', [NotificationController::class, 'unread']); //عرض الإشعارات غير المقروءة فقط
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);  //تحويل إشعار معين إلى مقروء
    Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']); // إضافة هذا المسار
    Route::post('/notifications/send-fcm', [NotificationController::class, 'sendFcmNotification']); // إضافة هذا المسار لإرسال إشعار فوري
});
