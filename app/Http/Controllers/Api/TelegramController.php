<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            Log::info('Telegram webhook received:', $update);

            // Check if it's a message update
            if (!isset($update['message'])) {
                Log::info('No message in update, skipping');
                return response()->json(['status' => 'ok']);
            }

            $message = $update['message'];

            // Validate required fields
            if (!isset($message['chat']['id']) || !isset($message['text'])) {
                Log::warning('Invalid message format', $message);
                return response()->json(['status' => 'ok']);
            }

            $chatId = (string) $message['chat']['id'];
            $text = trim($message['text']);
            $firstName = $message['from']['first_name'] ?? 'User';

            Log::info("Processing Telegram message", [
                'chat_id' => $chatId,
                'text' => $text,
                'name' => $firstName,
            ]);

            // Handle commands
            switch ($text) {
                case '/start':
                    $this->handleStartCommand($chatId, $firstName);
                    break;
                case '/stop':
                    $this->handleStopCommand($chatId);
                    break;
                case '/tasks':
                    $this->handleTasksCommand($chatId);
                    break;
                default:
                    $this->handleUnknownCommand($chatId);
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function handleStartCommand(string $chatId, string $name): void
    {
        try {
            Log::info("Handling /start command", [
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

            Log::info("User created or updated", [
                'wasRecentlyCreated' => $user->wasRecentlyCreated,
                'user_id' => $user->id,
            ]);

            $message = $user->wasRecentlyCreated
                ? "Welcome, {$name}! You've been subscribed to task notifications. ✅"
                : "Welcome back, {$name}! Your subscription has been reactivated. 🔔";

            $success = $this->telegramService->sendMessage($chatId, $message);

            if (!$success) {
                Log::error("Failed to send start message to chat: {$chatId}");
            }

        } catch (\Exception $e) {
            Log::error("Error in handleStartCommand: " . $e->getMessage());
        }
    }

    private function handleStopCommand(string $chatId): void
    {
        try {
            Log::info("Handling /stop command", ['chat_id' => $chatId]);

            $user = User::where('telegram_id', $chatId)->first();

            if ($user) {
                $user->update(['subscription' => false]);
                $message = "You've been unsubscribed from notifications. Use /start to resubscribe. 🔕";
            } else {
                $message = "User not found. Use /start to register first. ❌";
            }

            $success = $this->telegramService->sendMessage($chatId, $message);

            if (!$success) {
                Log::error("Failed to send stop message to chat: {$chatId}");
            }

        } catch (\Exception $e) {
            Log::error("Error in handleStopCommand: " . $e->getMessage());
        }
    }

    private function handleTasksCommand(string $chatId): void
    {
        try {
            Log::info("Handling /tasks command", ['chat_id' => $chatId]);

            $taskService = new \App\Services\TaskService();
            $tasks = $taskService->getIncompleteTasks();
            $message = $taskService->formatTasksMessage($tasks);

            $success = $this->telegramService->sendMessage($chatId, $message);

            if (!$success) {
                Log::error("Failed to send tasks message to chat: {$chatId}");
            }

        } catch (\Exception $e) {
            Log::error("Error in handleTasksCommand: " . $e->getMessage());
            $this->telegramService->sendMessage($chatId, "Sorry, there was an error fetching tasks.");
        }
    }

    private function handleUnknownCommand(string $chatId): void
    {
        $message = "Available commands:\n/start - Subscribe to notifications\n/stop - Unsubscribe from notifications\n/tasks - Get current incomplete tasks";

        $success = $this->telegramService->sendMessage($chatId, $message);

        if (!$success) {
            Log::error("Failed to send help message to chat: {$chatId}");
        }
    }


}
