<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// BR-USR-01: One super admin only, cannot be deleted
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('is_super_admin', true)->exists()) {
            $this->command->warn('Super admin already exists — skipping.');
            return;
        }

        User::create([
            'national_id'    => '408454700',
            'first_name'     => 'أحمد',
            'family_name'    => 'أبو عبادة',
            'gender'         => 'male',
            'password_hash'  => Hash::make('ahmad@etqan2654**'),
            'role'           => UserRole::SuperAdmin,
            'is_super_admin' => true,
            'is_active'      => true,
        ]);

        $this->command->info('Super admin created: 408454700');
    }
}
