<?php
// app/Http/Controllers/Api/TelegramController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TelegramController extends Controller
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function webhook(Request $request): JsonResponse
    {
        $update = $request->all();

        \Log::info('Telegram webhook received:', $update);

        if (!isset($update['message'])) {
            return response()->json(['status' => 'ok']);
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'User';

        \Log::info("Parsed Telegram message", [
        'chat_id' => $chatId,
        'text' => $text,
        'name' => $firstName,
        ]);

        switch ($text) {
            case '/start':
                $this->handleStartCommand($chatId, $firstName);
                break;
            case '/stop':
                $this->handleStopCommand($chatId);
                break;
            default:
                $this->telegramService->sendMessage(
                    $chatId,
                    "Available commands:\n/start - Subscribe to notifications\n/stop - Unsubscribe from notifications"
                );
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleStartCommand(string $chatId, string $name): void
    {
      \Log::info("Handling /start command", [
        'chat_id' => $chatId,
        'name' => $name,
      ]);

        $user = User::updateOrCreate(
            ['telegram_id' => $chatId],
            [
                'name' => $name,
                'subscription' => true,
            ]
        );

        \Log::info("User created or updated", [
            'wasRecentlyCreated' => $user->wasRecentlyCreated,
            'user_id' => $user->id,
        ]);

        $message = $user->wasRecentlyCreated
            ? "Welcome, {$name}! You've been subscribed to task notifications."
            : "Welcome back, {$name}! Your subscription has been reactivated.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    private function handleStopCommand(string $chatId): void
    {
        \Log::info("Handling /stop command", ['chat_id' => $chatId]);

        $user = User::where('telegram_id', $chatId)->first();

        if ($user) {
            $user->update(['subscription' => false]);
            $this->telegramService->sendMessage(
                $chatId,
                "You've been unsubscribed from notifications. Use /start to resubscribe."
            );
        } else {
            $this->telegramService->sendMessage(
                $chatId,
                "User not found. Use /start to register first."
            );
        }
    }
}
