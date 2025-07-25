<?php

namespace Tests\Feature;

use App\Console\Commands\NotifyTasks;
use App\Jobs\SendTelegramNotification;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotifyTasksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_tasks_command_sends_notifications_to_subscribed_users()
    {
        Queue::fake();

        $subscribedUser = User::create([
            'name' => 'John',
            'telegram_id' => '12345',
            'subscribed' => true
        ]);

        $unsubscribedUser = User::create([
            'name' => 'Jane',
            'telegram_id' => '67890',
            'subscribed' => false
        ]);

        $mockTasks = [
            [
                'id' => 1,
                'userId' => 1,
                'title' => 'Test task 1',
                'completed' => false
            ],
            [
                'id' => 2,
                'userId' => 2,
                'title' => 'Test task 2',
                'completed' => false
            ]
        ];

        $this->mock(TaskService::class, function ($mock) use ($mockTasks) {
            $mock->shouldReceive('getIncompleteTasks')
                ->once()
                ->andReturn($mockTasks);

            $mock->shouldReceive('formatTasksMessage')
                ->once()
                ->with($mockTasks)
                ->andReturn('<b>Incomplete Tasks:</b>\n\n• <b>Task #1</b> (User 1)\n  Test task 1\n\n• <b>Task #2</b> (User 2)\n  Test task 2\n\n');
        });

        $this->artisan('notify:tasks')
            ->expectsOutput('Starting task notification process...')
            ->expectsOutput('Found 2 incomplete tasks')
            ->expectsOutput('Found 1 subscribed users')
            ->expectsOutput('Queued notification for user: John')
            ->expectsOutput('All notifications have been queued successfully!')
            ->assertExitCode(0);

        Queue::assertPushed(SendTelegramNotification::class, function ($job) use ($subscribedUser) {
            return $job->chatId === $subscribedUser->telegram_id;
        });

        Queue::assertPushed(SendTelegramNotification::class, 1);
    }

    public function test_notify_tasks_command_handles_no_subscribed_users()
    {
        Queue::fake();

        User::create([
            'name' => 'Jane',
            'telegram_id' => '67890',
            'subscribed' => false
        ]);

        $this->mock(TaskService::class, function ($mock) {
            $mock->shouldReceive('getIncompleteTasks')
                ->once()
                ->andReturn([]);
        });

        $this->artisan('notify:tasks')
            ->expectsOutput('Starting task notification process...')
            ->expectsOutput('Found 0 incomplete tasks')
            ->expectsOutput('Found 0 subscribed users')
            ->expectsOutput('No subscribed users found')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_notify_tasks_command_handles_no_tasks()
    {
        Queue::fake();

        $subscribedUser = User::create([
            'name' => 'John',
            'telegram_id' => '12345',
            'subscribed' => true
        ]);

        $this->mock(TaskService::class, function ($mock) {
            $mock->shouldReceive('getIncompleteTasks')
                ->once()
                ->andReturn([]);

            $mock->shouldReceive('formatTasksMessage')
                ->once()
                ->with([])
                ->andReturn('<b>No incomplete tasks found!</b>');
        });

        $this->artisan('notify:tasks')
            ->expectsOutput('Starting task notification process...')
            ->expectsOutput('Found 0 incomplete tasks')
            ->expectsOutput('Found 1 subscribed users')
            ->expectsOutput('Queued notification for user: John')
            ->expectsOutput('All notifications have been queued successfully!')
            ->assertExitCode(0);

        Queue::assertPushed(SendTelegramNotification::class, 1);
    }

    public function test_notify_tasks_command_processes_multiple_subscribed_users()
    {
        Queue::fake();

        $user1 = User::create([
            'name' => 'John',
            'telegram_id' => '12345',
            'subscribed' => true
        ]);

        $user2 = User::create([
            'name' => 'Alice',
            'telegram_id' => '54321',
            'subscribed' => true
        ]);

        User::create([
            'name' => 'Bob',
            'telegram_id' => '99999',
            'subscribed' => false
        ]);

        $mockTasks = [
            [
                'id' => 1,
                'userId' => 1,
                'title' => 'Test task',
                'completed' => false
            ]
        ];

        $this->mock(TaskService::class, function ($mock) use ($mockTasks) {
            $mock->shouldReceive('getIncompleteTasks')
                ->once()
                ->andReturn($mockTasks);

            $mock->shouldReceive('formatTasksMessage')
                ->once()
                ->andReturn('Task message');
        });

        $this->artisan('notify:tasks')
            ->expectsOutput('Found 2 subscribed users')
            ->expectsOutput('Queued notification for user: John')
            ->expectsOutput('Queued notification for user: Alice')
            ->assertExitCode(0);

        Queue::assertPushed(SendTelegramNotification::class, 2);

        Queue::assertPushed(SendTelegramNotification::class, function ($job) use ($user1) {
            return $job->chatId === $user1->telegram_id;
        });

        Queue::assertPushed(SendTelegramNotification::class, function ($job) use ($user2) {
            return $job->chatId === $user2->telegram_id;
        });
    }
}
