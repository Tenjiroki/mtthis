<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Services\TaskService;
use App\Jobs\SendTelegramNotification;

Route::get('/', function () {
    return ('welcome');
});

// Original webhook setup route
Route::get('/setup-webhook', function (App\Services\TelegramService $telegramService) {
    $webhookUrl = url('/api/telegram/webhook');
    $result = $telegramService->setWebhook($webhookUrl);

    return $result ? 'Webhook setup successful!' : 'Webhook setup failed!';
});

// Debug routes - ADD THESE:

// Check users in database
Route::get('/check_users', function () {
    $users = User::all();
    return response()->json([
        'total_users' => $users->count(),
        'subscribed_users' => $users->where('subscription', true)->count(),
        'users' => $users->toArray()
    ]);
});

// Test notifications system
Route::get('/test_notifications', function () {
    $taskService = new TaskService();
    $tasks = $taskService->getIncompleteTasks();
    $users = User::subscribed()->get();

    if ($users->isEmpty()) {
        return response()->json(['error' => 'No subscribed users found']);
    }

    $message = $taskService->formatTasksMessage($tasks);

    // Send to all subscribed users
    foreach ($users as $user) {
        SendTelegramNotification::dispatch($user->telegram_id, $message);
    }

    return response()->json([
        'tasks_found' => count($tasks),
        'subscribed_users' => $users->count(),
        'message_preview' => substr($message, 0, 200) . '...',
        'status' => 'Notifications queued successfully'
    ]);
});

Route::get('/test-all-tasks', function () {
    try {
        $response = Http::get('https://jsonplaceholder.typicode.com/todos');
        $allTasks = $response->json();

        // Get all incomplete tasks (no userId filter)
        $allIncomplete = array_filter($allTasks, function ($task) {
            return !$task['completed'];
        });

        // Get incomplete for userId <= 5
        $filteredIncomplete = array_filter($allTasks, function ($task) {
            return !$task['completed'] && $task['userId'] <= 5;
        });

        $taskService = new TaskService();
        $message = $taskService->formatTasksMessage($filteredIncomplete);

        return response()->json([
            'total_tasks' => count($allTasks),
            'all_incomplete' => count($allIncomplete),
            'filtered_incomplete' => count($filteredIncomplete),
            'sample_incomplete' => array_slice($allIncomplete, 0, 3),
            'sample_filtered' => array_slice($filteredIncomplete, 0, 3),
            'formatted_message' => $message
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
// Test bot token
Route::get('/test-bot', function () {
    $botToken = env('TELEGRAM_BOT_TOKEN');
    $response = Http::withoutVerifying()
        ->get("https://api.telegram.org/bot{$botToken}/getMe");
    return response()->json($response->json());
});

// Enhanced webhook setup with debug info
Route::get('/setup-webhook-debug', function () {
    $botToken = env('TELEGRAM_BOT_TOKEN');
    $webhookUrl = url('/api/telegram/webhook');

    if (empty($botToken)) {
        return response()->json(['error' => 'TELEGRAM_BOT_TOKEN not set']);
    }

    $webhookResponse = Http::withoutVerifying()
        ->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
        ]);

    return response()->json([
        'webhook_url' => $webhookUrl,
        'webhook_response' => $webhookResponse->json(),
        'success' => $webhookResponse->successful()
    ]);
});

// Check current webhook info
Route::get('/webhook-info', function () {
    $botToken = env('TELEGRAM_BOT_TOKEN');
    $response = Http::withoutVerifying()
        ->get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
    return response()->json($response->json());
});

// Test fetching tasks from API
Route::get('/test-tasks', function () {
    $taskService = new TaskService();
    $tasks = $taskService->getIncompleteTasks();

    return response()->json([
        'total_tasks' => count($tasks),
        'tasks' => $tasks,
        'formatted_message' => $taskService->formatTasksMessage($tasks)
    ]);
});
Route::get('/debug-api', function () {
    try {
        $response = Http::get('https://jsonplaceholder.typicode.com/todos');
        $allTasks = $response->json();

        // Show first 10 tasks and filter stats
        $incompleteTasks = array_filter($allTasks, function ($task) {
            return !$task['completed'] && $task['userId'] <= 5;
        });

        return response()->json([
            'api_status' => $response->status(),
            'total_from_api' => count($allTasks),
            'incomplete_filtered' => count($incompleteTasks),
            'first_5_tasks' => array_slice($allTasks, 0, 5),
            'first_5_incomplete' => array_slice($incompleteTasks, 0, 5),
            'filter_criteria' => 'completed = false AND userId <= 5'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
});
