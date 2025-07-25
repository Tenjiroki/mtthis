<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $chatId,
        private string $message
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $success = $telegramService->sendMessage($this->chatId, $this->message);

        if (!$success) {
            Log::error("Failed to send message to chat: {$this->chatId}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendTelegramNotification job failed: " . $exception->getMessage());
    }
}
