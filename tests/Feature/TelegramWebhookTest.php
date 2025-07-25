<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_command_creates_new_user()
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('12345', 'Welcome, John! You\'ve been subscribed to task notifications. ✅')
                ->andReturn(true);
        });

        $webhookData = [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/start',
                'from' => ['first_name' => 'John']
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200)
                ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('users', [
            'telegram_id' => '12345',
            'name' => 'John',
            'subscribed' => true
        ]);
    }

    public function test_start_command_reactivates_existing_user()
    {
        User::create([
            'telegram_id' => '12345',
            'name' => 'John',
            'subscribed' => false
        ]);

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('12345', 'Welcome back, John! Your subscription has been reactivated. 🔔')
                ->andReturn(true);
        });

        $webhookData = [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/start',
                'from' => ['first_name' => 'John']
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'telegram_id' => '12345',
            'subscribed' => true
        ]);
    }

    public function test_stop_command_unsubscribes_user()
    {
        User::create([
            'telegram_id' => '12345',
            'name' => 'John',
            'subscribed' => true
        ]);

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('12345', 'You\'ve been unsubscribed from notifications. Use /start to resubscribe. 🔕')
                ->andReturn(true);
        });

        $webhookData = [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/stop',
                'from' => ['first_name' => 'John']
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'telegram_id' => '12345',
            'subscribed' => false
        ]);
    }

    public function test_stop_command_handles_nonexistent_user()
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('12345', 'User not found. Use /start to register first. ❌')
                ->andReturn(true);
        });

        $webhookData = [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/stop',
                'from' => ['first_name' => 'John']
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200);
    }

    public function test_unknown_command_returns_help()
    {
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('12345', 'Available commands:\n/start - Subscribe to notifications\n/stop - Unsubscribe from notifications\n/tasks - Get current incomplete tasks')
                ->andReturn(true);
        });

        $webhookData = [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/unknown',
                'from' => ['first_name' => 'John']
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200);
    }

    public function test_webhook_handles_invalid_message_format()
    {
        $webhookData = [
            'message' => [
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200)
                ->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_non_message_updates()
    {
        $webhookData = [
            'callback_query' => [
                'data' => 'some_data'
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $webhookData);

        $response->assertStatus(200)
                ->assertJson(['status' => 'ok']);
    }
}
