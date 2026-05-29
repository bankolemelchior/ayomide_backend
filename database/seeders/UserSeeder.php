<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@ayomide.com'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@ayomide.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );

        // Manager user
        User::updateOrCreate(
            ['email' => 'manager@ayomide.com'],
            [
                'name' => 'Manager',
                'email' => 'manager@ayomide.com',
                'email_verified_at' => now(),
                'password' => Hash::make('manager123'),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );

        // Utilisateur standard
        User::updateOrCreate(
            ['email' => 'user@ayomide.com'],
            [
                'name' => 'Utilisateur',
                'email' => 'user@ayomide.com',
                'email_verified_at' => now(),
                'password' => Hash::make('user123'),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );

        $this->command->info('✅ 3 utilisateurs créés avec succès !');
    }
}
