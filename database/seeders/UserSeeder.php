<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Hihi',
            'telegram_id' => '177013',
            'subscription' => true,
        ]);

        User::create([
            'name' => 'Haha',
            'telegram_id' => '141015679',
            'subscription' => false,
        ]);
    }
}
