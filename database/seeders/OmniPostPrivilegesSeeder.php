<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OmniPostPrivilegesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privileges = [
            // Brand privileges
            ['slug' => 'brand.view', 'name' => 'View Brands', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'brand.manage', 'name' => 'Manage Brands', 'created_at' => now(), 'updated_at' => now()],
            
            // Channel privileges
            ['slug' => 'channel.view', 'name' => 'View Channels', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'channel.connect', 'name' => 'Connect Channels', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'channel.manage', 'name' => 'Manage Channels', 'created_at' => now(), 'updated_at' => now()],
            
            // Post privileges
            ['slug' => 'post.view', 'name' => 'View Posts', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'post.create', 'name' => 'Create Posts', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'post.edit', 'name' => 'Edit Posts', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'post.approve', 'name' => 'Approve Posts', 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'post.publish', 'name' => 'Publish Posts', 'created_at' => now(), 'updated_at' => now()],
            
            // Calendar privileges
            ['slug' => 'calendar.view', 'name' => 'View Calendar', 'created_at' => now(), 'updated_at' => now()],
            
            // Analytics privileges
            ['slug' => 'analytics.view', 'name' => 'View Analytics', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($privileges as $privilege) {
            DB::table('privileges')->updateOrInsert(
                ['slug' => $privilege['slug']],
                $privilege
            );
        }

        // Attach privileges to roles
        $this->attachPrivilegesToRoles();
    }

    private function attachPrivilegesToRoles(): void
    {
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        $editorRole = DB::table('roles')->where('slug', 'editor')->first();

        if (!$adminRole) {
            DB::table('roles')->insert([
                'slug' => 'admin',
                'name' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        }

        if (!$managerRole) {
            DB::table('roles')->insert([
                'slug' => 'manager',
                'name' => 'Manager',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $managerRole = DB::table('roles')->where('slug', 'manager')->first();
        }

        if (!$editorRole) {
            DB::table('roles')->insert([
                'slug' => 'editor',
                'name' => 'Editor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $editorRole = DB::table('roles')->where('slug', 'editor')->first();
        }

        // Admin gets all privileges
        $allPrivileges = DB::table('privileges')->whereIn('slug', [
            'brand.view', 'brand.manage',
            'channel.view', 'channel.connect', 'channel.manage',
            'post.view', 'post.create', 'post.edit', 'post.approve', 'post.publish',
            'calendar.view', 'analytics.view'
        ])->pluck('id');

        foreach ($allPrivileges as $privilegeId) {
            DB::table('privilege_role')->updateOrInsert([
                'role_id' => $adminRole->id,
                'privilege_id' => $privilegeId,
            ]);
        }

        // Manager gets view and some management privileges
        $managerPrivileges = DB::table('privileges')->whereIn('slug', [
            'brand.view',
            'channel.view',
            'post.view', 'post.approve',
            'calendar.view', 'analytics.view'
        ])->pluck('id');

        foreach ($managerPrivileges as $privilegeId) {
            DB::table('privilege_role')->updateOrInsert([
                'role_id' => $managerRole->id,
                'privilege_id' => $privilegeId,
            ]);
        }

        // Editor gets content creation privileges
        $editorPrivileges = DB::table('privileges')->whereIn('slug', [
            'brand.view',
            'channel.view',
            'post.view', 'post.create', 'post.edit',
            'calendar.view'
        ])->pluck('id');

        foreach ($editorPrivileges as $privilegeId) {
            DB::table('privilege_role')->updateOrInsert([
                'role_id' => $editorRole->id,
                'privilege_id' => $privilegeId,
            ]);
        }
    }
}
