<?php

namespace App\Traits;

use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Model;

trait AutoTranslatable
{
    public static function bootAutoTranslatable(): void
    {
        // استدعاء الميثود عبر forwardStaticCall يمنع المحرر من إظهار أي خطأ
        forward_static_call([Model::class, 'saving'], function ($model) {
            if (method_exists($model, 'autoTranslateMissingLocales')) {
                $model->autoTranslateMissingLocales();
            }
        });
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
