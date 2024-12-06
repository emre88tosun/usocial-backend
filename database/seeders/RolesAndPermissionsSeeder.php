<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'manage users', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage gems', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage influencers', 'guard_name' => 'api']);
        Permission::create(['name' => 'send messages', 'guard_name' => 'api']);
        Permission::create(['name' => 'set gem cost', 'guard_name' => 'api']);

        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo(Permission::all());

        $standardUser = Role::create(['name' => 'standard user', 'guard_name' => 'api']);
        $standardUser->givePermissionTo(['send messages']);

        $influencer = Role::create(['name' => 'influencer', 'guard_name' => 'api']);
        $influencer->givePermissionTo(['set gem cost', 'send messages']);
    }
}
