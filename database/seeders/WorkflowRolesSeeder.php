<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create approver role if it doesn't exist
        $approverRole = DB::table('roles')->where('slug', 'approver')->first();
        
        if (!$approverRole) {
            DB::table('roles')->insert([
                'slug' => 'approver',
                'name' => 'Approver',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $approverRole = DB::table('roles')->where('slug', 'approver')->first();
        }

        // Assign privileges to approver role
        $approverPrivileges = DB::table('privileges')->whereIn('slug', [
            'brand.view',
            'channel.view',
            'post.view',
            'post.approve',
            'calendar.view',
            'analytics.view'
        ])->pluck('id');

        foreach ($approverPrivileges as $privilegeId) {
            DB::table('privilege_role')->updateOrInsert([
                'role_id' => $approverRole->id,
                'privilege_id' => $privilegeId,
            ]);
        }

        $this->command->info('Approver role created and privileges assigned successfully!');
    }
}
