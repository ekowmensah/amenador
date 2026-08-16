<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate([
            'name' => 'reports.view_own',
            'guard_name' => 'web',
        ]);
        Permission::firstOrCreate([
            'name' => 'activity_log.view',
            'guard_name' => 'web',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['reports.view_own', 'activity_log.view'])
            ->where('guard_name', 'web')
            ->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
