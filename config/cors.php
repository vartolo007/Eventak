<?php

return [

    /*
    |--------------------------------------------------------------------------
    | إعدادات Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | هذه الإعدادات تحدد الدومينات المسموح لها بمناداة الـ API من المتصفح.
    | الميدل وير المسؤول عن تطبيقها هو Illuminate\Http\Middleware\HandleCors
    | وهو جزء من الـ global middleware stack المعرّف في bootstrap/app.php.
    |
    */

    // المسارات التي تُطبَّق عليها قواعد CORS (كل مسارات الـ API)
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // الميثودز المسموحة (OPTIONS يتكفل بها HandleCors تلقائياً للـ preflight)
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     | الدومينات المسموح لها بالوصول.
     | تُضبط من ملف .env عبر CORS_ALLOWED_ORIGINS مفصولة بفاصلة، مثال:
     | CORS_ALLOWED_ORIGINS=https://eventak-front.com,http://localhost:5173
     | القيم الافتراضية أدناه تغطي بيئة التطوير المحلية للفرونت (Vite).
     */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:5173,http://localhost:4173,http://127.0.0.1:5173,http://127.0.0.1:4173,https://eventak.abukm.com'
        ))
    ))),

    // أنماط دومينات (regex) — تُترك فارغة ما لم نحتج نطاقات فرعية ديناميكية
    'allowed_origins_patterns' => [],

    /*
     | الهيدرز المسموح للفرونت بإرسالها.
     | X-Language و X-Lang مطلوبان لأن SetLocaleFromHeader يقرأ منهما اللغة.
     */
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-Language',
        'X-Lang',
    ],

    // هيدرز نسمح للفرونت بقراءتها من الاستجابة
    'exposed_headers' => [],

    // مدة تخزين المتصفح لنتيجة الـ preflight بالثواني (0 = بدون تخزين، أسهل للتشخيص)
    'max_age' => (int) env('CORS_MAX_AGE', 0),

    /*
     | false لأن المصادقة عندنا عبر توكن Sanctum في هيدر Authorization وليست
     | عبر كوكيز. لا تفعّلها إلا إذا انتقلنا لمصادقة SPA بالكوكيز، وعندها
     | يجب ألا تحتوي allowed_origins على '*' إطلاقاً.
     */
    'supports_credentials' => false,

];
