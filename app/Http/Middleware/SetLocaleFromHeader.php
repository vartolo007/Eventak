<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Language')
            ?? $request->header('X-Lang')
            ?? $request->header('Accept-Language');

        $resolvedLocale = $this->resolveLocale($locale);

        if ($resolvedLocale) {
            app()->setLocale($resolvedLocale);
            config(['app.locale' => $resolvedLocale]);

            $user = $request->user();
            if ($user && $user->locale !== $resolvedLocale) {
                $user->update(['locale' => $resolvedLocale]);
            }
        }

        return $next($request);
    }

    protected function resolveLocale(?string $locale): ?string
    {
        if (blank($locale)) {
            return null;
        }

        $candidate = strtolower(trim($locale));
        $candidate = strtok($candidate, ',');
        $candidate = str_replace('_', '-', $candidate);
        $candidate = preg_replace('/[^a-z-]/i', '', $candidate);

        if (in_array($candidate, ['ar', 'ar-sa', 'arabic'], true)) {
            return 'ar';
        }

        if (in_array($candidate, ['en', 'en-us', 'english'], true)) {
            return 'en';
        }

        return null;
    }
}
