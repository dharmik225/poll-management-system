<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // userfactory with run but also create a admin user
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@yopmail.com',
            'password' => 'Password',
            'is_admin' => true,
        ]);
        User::factory()->count(50)->create();
    }
}
