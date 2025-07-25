<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'https://jsonplaceholder.typicode.com/todos';
    }

    public function getIncompleteTasks(): array
    {
        try {
            $response = Http::get($this->apiUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch tasks from API');
            }

            $tasks = $response->json();

            return array_filter($tasks, function ($task) {
                return !$task['completed'] && $task['userId'] <= 5;
            });
        } catch (\Exception $e) {
            Log::error('Task API error: ' . $e->getMessage());
            return [];
        }
    }

    public function formatTasksMessage(array $tasks): string
    {
        if (empty($tasks)) {
            return "<b>No incomplete tasks found!</b>";
        }

        $message = "<b>Incomplete Tasks:</b>\n\n";

        foreach ($tasks as $task) {
            $message .= "• <b>Task #{$task['id']}</b> (User {$task['userId']})\n";
            $message .= "  {$task['title']}\n\n";
        }

        return $message;
    }
}
