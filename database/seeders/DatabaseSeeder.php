<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        // Create roles (or get existing ones)
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create SuperAdmin (or update if exists)
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@ghlinks.com'],
            [
                'name' => 'Super Admin',
                'phone' => '0200000000',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        if (!$superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }

        // Create Admin (or update if exists)
        $admin = User::updateOrCreate(
            ['email' => 'admin@ghlinks.com'],
            [
                'name' => 'Admin User',
                'phone' => '0200000001',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create Sample User (or update if exists)
        $testUser = User::updateOrCreate(
            ['phone' => '0241234567'],
            [
                'name' => 'Test User',
                'email' => null,
                'password' => null,
                'status' => 'active',
            ]
        );
        if (!$testUser->hasRole('user')) {
            $testUser->assignRole('user');
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('---');
        $this->command->info('SuperAdmin Login:');
        $this->command->info('Email: superadmin@ghlinks.com');
        $this->command->info('Password: password');
        $this->command->info('---');
        $this->command->info('Admin Login:');
        $this->command->info('Email: admin@ghlinks.com');
        $this->command->info('Password: password');
        $this->command->info('---');
        $this->command->info('Test User (OTP Login):');
        $this->command->info('Phone: 0241234567');
    }
}
