<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'microsoft_id' => null,
                'microsoft_account' => false,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'phone' => '1234567890',
                'bio' => 'I am the administrator of this platform.',
                'password' => Hash::make('12345678'), // Replace with a strong password
                'pfp' => null,
                'cover_photo' => null,
                'email_verified_at' => now(),
                'verification_token' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'email' => 'user1@example.com',
                'microsoft_id' => null,
                'microsoft_account' => false,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'johndoe',
                'phone' => '1234567891',
                'bio' => 'I am John Doe, a sample user.',
                'password' => Hash::make('password123'),
                'pfp' => null,
                'cover_photo' => null,
                'email_verified_at' => now(),
                'verification_token' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'email' => 'user2@example.com',
                'microsoft_id' => 'microsoft456',
                'microsoft_account' => false,
                'first_name' => 'Alice',
                'last_name' => 'Wonder',
                'username' => 'alicewonder',
                'phone' => '1234567892',
                'bio' => 'I am Alice Wonder, a tutor user.',
                'password' => Hash::make('password123'),
                'pfp' => null,
                'cover_photo' => null,
                'email_verified_at' => now(),
                'verification_token' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'email' => 'user3@example.com',
                'microsoft_id' => null,
                'microsoft_account' => false,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'username' => 'janesmith',
                'phone' => '1234567893',
                'bio' => 'I am Jane Smith, a tutor user.',
                'password' => Hash::make('password123'),
                'pfp' => null,
                'cover_photo' => null,
                'email_verified_at' => now(),
                'verification_token' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'email' => 'user4@example.com',
                'microsoft_id' => null,
                'microsoft_account' => false,
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'username' => 'charliebrown',
                'phone' => '1234567894',
                'bio' => 'I am Charlie Brown, a sample user.',
                'password' => Hash::make('password123'),
                'pfp' => null,
                'cover_photo' => null,
                'email_verified_at' => now(),
                'verification_token' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
        DB::table('roles_users')->insert([
            ['user_id' => 1, 'role_id' => 1], // Admin
            ['user_id' => 2, 'role_id' => 2], // Tutor
            ['user_id' => 3, 'role_id' => 2], // Tutor
            ['user_id' => 4, 'role_id' => 3], // Learner
            ['user_id' => 5, 'role_id' => 3], // Learner
        ]);
    }
}
