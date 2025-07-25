<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramNotification;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Console\Command;

class NotifyTasks extends Command
{
    protected $signature = 'notify:tasks';
    protected $description = 'Send incomplete tasks notification to subscribed users';

    public function __construct(
        private TaskService $taskService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting task notification process...');

        $tasks = $this->taskService->getIncompleteTasks();
        $this->info('Found ' . count($tasks) . ' incomplete tasks');

        $users = User::subscribed()->get();
        $this->info('Found ' . $users->count() . ' subscribed users');

        if ($users->isEmpty()) {
            $this->warn('No subscribed users found');
            return self::SUCCESS;
        }

        $message = $this->taskService->formatTasksMessage($tasks);

        foreach ($users as $user) {
            SendTelegramNotification::dispatch($user->telegram_id, $message);
            $this->info("Queued notification for user: {$user->name}");
        }

        $this->info('All notifications have been queued successfully!');
        return self::SUCCESS;
    }
}
