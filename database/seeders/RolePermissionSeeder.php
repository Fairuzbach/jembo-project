<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Permission Dulu
        $permissions = [
            'dashboard.view',
            'wo.create',
            'wo.edit',
            'wo.delete',
            'wo.process',
            'wo.approve_ga',
            'wo.approve_fh',
            'wo.approve_it',
            'wo.approve_mt',
            'wo.approve_qr',
            'wo.approve_sc',
            'wo.approve_ss',
            'wo.approve_sales1',
            'wo.approve_sales2',
            'wo.approve_lv',
            'wo.approve_mv',
            'wo.approve_hc',
            'wo.approve_fo',
            'wo.approve_fa',
            'wo.approve_mkt',
            'wo.approve_pe',
            'wo.approve_pp',
            'wo.approve_autowire',
            'wo.approve_ccv',
            'user.manage', // Izin untuk buka menu user management
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // 2. Buat Role & Assign Permission Spesifik
        // Kita pakai Array agar kodingan lebih pendek dan rapi
        $rolesData = [
            'eng.admin'       => 'wo.approve_pe',
            'fh.admin'        => 'wo.approve_fh',
            'it.admin'        => 'wo.approve_it',
            'mt.admin'        => 'wo.approve_mt',
            'qr.admin'        => 'wo.approve_qr',
            'sc.admin'        => 'wo.approve_sc',
            'sales1.admin'    => 'wo.approve_sales1',
            'sales2.admin'    => 'wo.approve_sales2',
            'ss.admin'        => 'wo.approve_ss',
            'lv.admin'        => 'wo.approve_lv',
            'mv.admin'        => 'wo.approve_mv',
            'fo.admin'        => 'wo.approve_fo',
            'hc.admin'        => 'wo.approve_hc',
            'fa.admin'        => 'wo.approve_fa',
            'marketing.admin' => 'wo.approve_mkt',
            'pp.admin' => 'wo.approve_pp',
            'autowire.admin' => 'wo.approve_autowire',
            'ccv.admin' => 'wo.approve_ccv'
        ];

        foreach ($rolesData as $roleName => $permissionName) {
            // PENTING: Pakai firstOrCreate agar tidak error "RoleDoesNotExist"
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->givePermissionTo($permissionName);
        }

        // 3. Role GA Admin (Setara Super Admin)
        $roleGa = Role::firstOrCreate(['name' => 'ga.admin']);
        $gaAdminRole = Permission::all()->where('name', '!=', 'user.manage');
        $roleGa->givePermissionTo($gaAdminRole);

        // 4. Role Super Admin (Paling Tinggi)
        $superGaAdmin = Role::firstOrCreate(['name' => 'super.ga.admin']);
        $superGaAdmin->givePermissionTo(Permission::all());
        $superAdmin = Role::firstOrCreate(['name' => 'super.admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 5. Role User Biasa
        $userRole = Role::firstOrCreate(['name' => 'User']);
        $userRole->givePermissionTo(['wo.create']);

        // 6. Buat Akun Default (Opsional, buat login pertama kali)
        $user = User::firstOrCreate([
            'email' => 'admin@jembo.com'
        ], [
            'name'     => 'Administrator',
            'nik'      => 'aezakmy',
            'divisi' => 'IT',
            'password' => bcrypt('password')
        ]);

        $user->assignRole('super.admin');
    }
}
