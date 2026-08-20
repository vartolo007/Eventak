<?php

namespace App\Traits;

use App\Services\TranslationService;

trait AutoTranslatable
{
    public static function bootAutoTranslatable(): void
    {
        static::saving(function ($model) {
            try {
                $model->fixTranslationLocales();
                $model->autoTranslateMissingLocales();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('AutoTranslatable saving error', [
                    'model' => get_class($model),
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    protected function detectTextLocale(string $text): string
    {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        return 'en';
    }

    protected function fixTranslationLocales(): void
    {
        if (!method_exists($this, 'getTranslatableAttributes') || !method_exists($this, 'getTranslations')) {
            return;
        }

        foreach ($this->getTranslatableAttributes() as $field) {
            $translations = $this->getTranslations($field);

            if (empty($translations)) {
                continue;
            }

            $corrected = [];
            $needsFix = false;

            foreach ($translations as $locale => $text) {
                if (!is_string($text) || empty(trim($text))) {
                    $corrected[$locale] = $text;
                    continue;
                }

                $detected = $this->detectTextLocale($text);

                if ($detected !== $locale && !isset($translations[$detected])) {
                    $corrected[$detected] = $text;
                    $needsFix = true;
                } else {
                    $corrected[$locale] = $text;
                }
            }

            if ($needsFix) {
                $this->attributes[$field] = $this->asJson($corrected);
            }
        }
    }

    public function toArray(): array
    {
        $array = method_exists($this, 'attributesToArray') ? $this->attributesToArray() : [];
        $array = array_merge($array, method_exists($this, 'relationsToArray') ? $this->relationsToArray() : []);
        $locale = app()->getLocale();

        if (method_exists($this, 'getTranslatableAttributes')) {
            foreach ($this->getTranslatableAttributes() as $field) {
                if (array_key_exists($field, $array) && method_exists($this, 'getTranslation')) {
                    $array[$field] = $this->getTranslation($field, $locale);
                }
            }
        }

        return $array;
    }

    protected function autoTranslateMissingLocales(): void
    {
        $supportedLocales = ['ar', 'en'];
        $translationService = app(TranslationService::class);

        if (!method_exists($this, 'getTranslatableAttributes')) {
            return;
        }

        foreach ($this->getTranslatableAttributes() as $field) {
            if (!method_exists($this, 'getTranslations')) {
                continue;
            }

            $translations = $this->getTranslations($field);

            if (empty($translations)) {
                continue;
            }

            $filledLocales = array_filter($translations, fn($v) => !empty(trim((string) $v)));
            $missingLocales = array_diff($supportedLocales, array_keys($filledLocales));

            if (empty($missingLocales) || empty($filledLocales)) {
                continue;
            }

            $sourceLocale = array_key_first($filledLocales);
            $sourceText = $filledLocales[$sourceLocale];

            foreach ($missingLocales as $targetLocale) {
                $translated = $translationService->translate($sourceText, $sourceLocale, $targetLocale);
                if ($translated && method_exists($this, 'setTranslation')) {
                    $this->setTranslation($field, $targetLocale, $translated);
                }
            }
        }
    }
}
