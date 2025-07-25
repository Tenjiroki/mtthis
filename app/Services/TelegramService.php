<?php
// app/Services/TelegramService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram API error: ' . $e->getMessage());
            return false;
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/setWebhook", [
                'url' => $url,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Webhook setup error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUpdates(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/getUpdates");
            return $response->successful() ? $response->json()['result'] : [];
        } catch (\Exception $e) {
            Log::error('Get updates error: ' . $e->getMessage());
            return [];
        }
    }
}
