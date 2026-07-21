<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Rating;

class RatingController extends Controller
{
    // 1. إضافة تقييم لمناسبة مكتملة (من 1 إلى 5 نجوم مع تعليق اختياري)
    public function store(Request $request, $eventId)
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $event = Event::findOrFail($eventId);

        // التحقق من أن المناسبة تابعة للزبون الحالي
        if ($event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية تقييم هذه المناسبة'
            ], 403);
        }

        // لا يمكن التقييم إلا بعد اكتمال المناسبة فعلياً
        if ($event->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكنك تقييم المناسبة إلا بعد اكتمالها.'
            ], 422);
        }

        // منع التقييم المزدوج لنفس المناسبة
        if (Rating::where('event_id', $event->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'لقد قمت بتقييم هذه المناسبة مسبقاً.'
            ], 422);
        }

        $rating = Rating::create([
            'event_id' => $event->id,
            'customer_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'شكراً لك! تم تسجيل تقييمك بنجاح.',
            'data' => $rating
        ], 201);
    }

    // 2. عرض تقييم الزبون لمناسبة معينة (إن وجد)
    public function show(Request $request, $eventId)
    {
        $user = $request->user();
        $event = Event::findOrFail($eventId);

        if ($event->customer_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية عرض تقييم هذه المناسبة'
            ], 403);
        }

        $rating = Rating::where('event_id', $event->id)->first();

        return response()->json([
            'status' => 'success',
            'data' => $rating // null إذا لم يتم التقييم بعد
        ]);
    }

    // 3. عرض تقييمات صالة معينة (عام - متاح للجميع) مع المعدل وعدد التقييمات
    public function venueRatings($venueId)
    {
        $ratings = Rating::whereHas('event', function ($query) use ($venueId) {
                $query->where('venue_id', $venueId);
            })
            ->with('customer:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'average_rating' => round($ratings->avg('rating') ?? 0, 1),
                'ratings_count' => $ratings->count(),
                'ratings' => $ratings->map(fn($r) => [
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'customer_name' => $r->customer?->name,
                    'created_at' => $r->created_at,
                ]),
            ]
        ]);
    }
}
