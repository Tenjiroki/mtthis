<?php

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

        if (empty($this->botToken)) {
            Log::error('TELEGRAM_BOT_TOKEN is not set in environment');
        }
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        try {
            Log::info("Sending message to Telegram", [
                'chat_id' => $chatId,
                'message_length' => strlen($text)
            ]);

            $response = Http::withoutVerifying() 
                ->timeout(30)
                ->post("{$this->baseUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);

            if ($response->successful()) {
                Log::info("Message sent successfully to chat: {$chatId}");
                return true;
            } else {
                Log::error("Telegram API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'chat_id' => $chatId
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Telegram API error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            Log::info("Setting webhook", ['url' => $url]);

            $response = Http::withoutVerifying() // Disable SSL verification
                ->timeout(30)
                ->post("{$this->baseUrl}/setWebhook", [
                    'url' => $url,
                ]);

            if ($response->successful()) {
                Log::info("Webhook set successfully", ['url' => $url]);
                return true;
            } else {
                Log::error("Webhook setup failed", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Webhook setup error: ' . $e->getMessage());
            return false;
        }
    }

    public function getMe(): array
    {
        try {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/getMe");
            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            Log::error('Get bot info error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUpdates(): array
    {
        try {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/getUpdates");
            return $response->successful() ? $response->json()['result'] : [];
        } catch (\Exception $e) {
            Log::error('Get updates error: ' . $e->getMessage());
            return [];
        }
    }
}
