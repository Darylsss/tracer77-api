<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'gerer_espace']);
        Permission::firstOrCreate(['name' => 'voir_positions']);
        Permission::firstOrCreate(['name' => 'ajouter_lieu']);

        $admin = Role::firstOrCreate(['name' => 'admin_famille']);
        $admin->syncPermissions(['gerer_espace', 'voir_positions', 'ajouter_lieu']);

        $membre = Role::firstOrCreate(['name' => 'membre']);
        $membre->syncPermissions(['voir_positions']);
    }
}