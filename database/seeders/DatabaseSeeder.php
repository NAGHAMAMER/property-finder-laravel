<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'password' => env('ADMIN_PASSWORD', 'Admin@123456'),
                'role' => 'admin',
            ]
        );
    }
}
