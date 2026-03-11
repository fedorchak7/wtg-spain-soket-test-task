<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'];

        foreach ($users as $name) {
            User::create([
                'name' => $name,
                'email' => strtolower($name) . '@example.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
