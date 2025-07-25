Laravel Telegram Bot
A Laravel-based service that fetches tasks from an external API and sends notifications to subscribed users via Telegram bot.
Features

Telegram Bot Integration: Handle /start, /stop, and /tasks commands
User Management: Store users with subscription preferences
Task Notifications: Fetch incomplete tasks and send notifications
Queue System: Asynchronous message sending using Laravel queues
Console Commands: Automated task notification system

Requirements

PHP 8.1+
Laravel 9-12
MySQL/PostgreSQL database
Telegram Bot Token
Queue driver (database, Redis, etc.)

Installation
1. Clone the Repository
bashgit clone <your-repository-url>
cd telegram-task-bot
2. Install Dependencies
bashcomposer install
3. Environment Setup
bashcp .env.example .env
php artisan key:generate
4. Configure Environment Variables
Edit .env file with your settings:
env# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_here

# Queue Configuration
QUEUE_CONNECTION=database
5. Database Setup
bashphp artisan migrate
php artisan db:seed
6. Set Up Queue Worker
bashphp artisan queue:work

Telegram Bot Setup
1. Create a Telegram Bot

Message @BotFather on Telegram
Use /newbot command
Follow instructions to get your bot token
Add the token to your .env file

2. Set Up Webhook
Visit the webhook setup route in your browser:
http://your-domain.com/setup-webhook
Or use the debug version for more information:
http://your-domain.com/setup-webhook-debug

Bot Commands
CommandDescription/startSubscribe to task notifications/stopUnsubscribe from notifications/tasksGet current incomplete tasks

Usage
Running the Notification Command
Send notifications to all subscribed users:
bashphp artisan notify:tasks
Schedule the Command (Optional)
Add to app/Console/Kernel.php:
phpprotected function schedule(Schedule $schedule)
{
    $schedule->command('notify:tasks')->hourly();
}
Then run the scheduler:
bashphp artisan schedule:work

Testing
Run All Tests
bashphp artisan test
Run Specific Test Suites
bash# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Feature/TelegramWebhookTest.php
Test Coverage
The following components are tested:

Task fetching and formatting (TaskServiceTest)
Telegram webhook handling (TelegramWebhookTest)
Console command execution (NotifyTasksCommandTest)

 API Endpoints
Telegram Webhook
POST /api/telegram/webhook
Handles incoming Telegram updates.
Debug Routes
Available for testing and debugging:
RouteDescriptionGET /check_usersView all users in databaseGET /test_notificationsTest notification systemGET /test-tasksTest task fetchingGET /webhook-infoCheck current webhook statusGET /test-botVerify bot token

Database Schema
Users Table
sqlCREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    telegram_id VARCHAR(255) UNIQUE NOT NULL,
    subscribed BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
