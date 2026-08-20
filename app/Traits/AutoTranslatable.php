<?php

namespace App\Traits;

use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Model;

trait AutoTranslatable
{
    public static function bootAutoTranslatable(): void
    {
        forward_static_call([Model::class, 'saving'], function ($model) {
            if (method_exists($model, 'autoTranslateMissingLocales')) {
                $model->autoTranslateMissingLocales();
            }
        });
    }

    /**
     * كشف لغة النص وحفظه تحت المفتاح الصحيح بدل الاعتماد الأعمى على app locale.
     */
    public function setAttribute($key, $value)
    {
        if (
            is_string($value)
            && !empty(trim($value))
            && method_exists($this, 'isTranslatableAttribute')
            && $this->isTranslatableAttribute($key)
        ) {
            $detectedLocale = $this->detectTextLocale($value);
            $this->setTranslation($key, $detectedLocale, $value);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected function detectTextLocale(string $text): string
    {
        // إذا فيه حروف عربية → عربي، وإلا → إنكليزي
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        return 'en';
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
