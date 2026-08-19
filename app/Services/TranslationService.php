<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.libretranslate.url', 'http://localhost:5000'), '/');
        $this->apiKey = config('services.libretranslate.api_key');
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        if (empty(trim($text))) {
            return $text;
        }

        try {
            $payload = [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ];

            if ($this->apiKey) {
                $payload['api_key'] = $this->apiKey;
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/translate", $payload);

            if ($response->successful()) {
                return $response->json('translatedText');
            }

            Log::warning('LibreTranslate API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('LibreTranslate connection failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
