<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        \App\Models\User::firstOrCreate(
            ['netID' => 'ABCD1234'], #I WOULD PUT YOUR NETID HERE SO THAT YOU DONT HAVE TO MANUALLY ADD IT
            ['isAdmin' => true]
        );
    }
}
