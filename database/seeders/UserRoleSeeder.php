<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // 1. User Umum (Bisa dipakai banyak orang)
        User::updateOrCreate(
            ['email' => 'user@jembo.com'],
            [
                'nik' => '1001',
                'name' => 'User',
                'password' => Hash::make('password'),
                'role' => 'user', // Role USER biasa
                'divisi' => 'General',
                'jabatan' => 'User',
                'is_active' => true
            ]
        );

        // 2. GA Admin
        User::updateOrCreate(
            ['email' => 'ga@jembo.com'],
            [
                'nik' => '1002',
                'name' => 'Admin GA',
                'password' => Hash::make('password'),
                'role' => 'ga.admin', // Role Admin GA
                'divisi' => 'General Affair',
                'jabatan' => 'Admin',
                'is_active' => true
            ]
        );
        //Engineer
        User::updateOrCreate(
            ['email' => 'engineer@jembo.com'],
            [
                'nik' => '1003',
                'name' => 'Admin Engineer',
                'password' => Hash::make('password'),
                'role' => 'eng.admin',
                'divisi' => 'Engineering',
                'jabatan' => 'Admin',
                'is_active' => true
            ]
        );
        //Facility
        User::updateOrCreate(
            ['email' => 'facility@jembo.com'],
            [
                'nik' => '1004',
                'name' => 'Admin Facility',
                'password' => Hash::make('password'),
                'role' => 'fh.admin',
                'divisi' => 'Facility',
                'jabatan' => 'Admin',
                'is_active' => true
            ]
        );
        User::updateOrCreate(
            ['email' => 'maintenance@jembo.com'],
            [
                'nik' => '1005',
                'name' => 'Admin Maintenance',
                'password' => Hash::make('password'),
                'role' => 'mt.admin',
                'divisi' => 'Maintenance',
                'jabatan' => 'Admin',
                'is_active' => true
            ]
        );


        // ... Buat juga untuk mt.admin, eng.admin, fh.admin sesuai kebutuhan
    }
}
