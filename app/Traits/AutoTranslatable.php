<?php

namespace App\Traits;

use App\Services\TranslationService;

trait AutoTranslatable
{
    public static function bootAutoTranslatable(): void
    {
        static::saving(function ($model) {
            $model->autoTranslateMissingLocales();
        });
    }

    protected function autoTranslateMissingLocales(): void
    {
        $supportedLocales = ['ar', 'en'];
        $translationService = app(TranslationService::class);

        foreach ($this->getTranslatableAttributes() as $field) {
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
                if ($translated) {
                    $this->setTranslation($field, $targetLocale, $translated);
                }
            }
        }
    }
}
