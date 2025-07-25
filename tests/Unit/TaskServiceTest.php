<?php

namespace Tests\Unit;

use App\Services\TaskService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
    }

    public function test_get_incomplete_tasks_returns_filtered_tasks()
    {
        Http::fake([
            'jsonplaceholder.typicode.com/todos' => Http::response([
                [
                    'id' => 1,
                    'userId' => 1,
                    'title' => 'Task 1',
                    'completed' => false
                ],
                [
                    'id' => 2,
                    'userId' => 2,
                    'title' => 'Task 2',
                    'completed' => true
                ],
                [
                    'id' => 3,
                    'userId' => 6,
                    'title' => 'Task 3',
                    'completed' => false
                ],
                [
                    'id' => 4,
                    'userId' => 3,
                    'title' => 'Task 4',
                    'completed' => false
                ]
            ])
        ]);

        $tasks = $this->taskService->getIncompleteTasks();

        $this->assertCount(2, $tasks);
        $this->assertEquals(1, $tasks[0]['id']);
        $this->assertEquals(4, $tasks[1]['id']);
        $this->assertFalse($tasks[0]['completed']);
        $this->assertFalse($tasks[1]['completed']);
    }

    public function test_get_incomplete_tasks_handles_api_failure()
    {
        Http::fake([
            'jsonplaceholder.typicode.com/todos' => Http::response([], 500)
        ]);

        $tasks = $this->taskService->getIncompleteTasks();

        $this->assertEmpty($tasks);
    }

    public function test_format_tasks_message_with_tasks()
    {
        $tasks = [
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

        $message = $this->taskService->formatTasksMessage($tasks);

        $this->assertStringContainsString('<b>Incomplete Tasks:</b>', $message);
        $this->assertStringContainsString('Task #1', $message);
        $this->assertStringContainsString('Task #2', $message);
        $this->assertStringContainsString('Test task 1', $message);
        $this->assertStringContainsString('Test task 2', $message);
        $this->assertStringContainsString('User 1', $message);
        $this->assertStringContainsString('User 2', $message);
    }

    public function test_format_tasks_message_with_empty_tasks()
    {
        $message = $this->taskService->formatTasksMessage([]);

        $this->assertEquals('<b>No incomplete tasks found!</b>', $message);
    }
}
