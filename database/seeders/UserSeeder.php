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
        // Buat test users untuk setiap divisi
        User::create([
            'nik' => '9001',
            'name' => 'Admin General Affair',
            'email' => 'ga@example.com',
            'divisi' => 'General Affair',
            'role' => 'ga.admin',
            'jabatan' => 'Head of Department',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'nik' => '9002',
            'name' => 'Admin Engineering',
            'email' => 'eng@example.com',
            'divisi' => 'Engineering',
            'role' => 'eng.admin',
            'jabatan' => 'Head of Department',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'nik' => '9003',
            'name' => 'Admin Facilities',
            'email' => 'fh@example.com',
            'divisi' => 'Facilities',
            'role' => 'fh.admin',
            'jabatan' => 'Head of Department',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'nik' => '9004',
            'name' => 'Admin Maintenance',
            'email' => 'mt@example.com',
            'divisi' => 'Maintenance',
            'role' => 'mt.admin',
            'jabatan' => 'Head of Department',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // Create additional test users
        User::factory(10)->create();
    }
}
