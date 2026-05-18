<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

// BR-USR-01: One super admin only, cannot be deleted. Credentials are sourced from .env
// so rotating SUPER_ADMIN_PASSWORD is a one-line change followed by re-running this command.
class SyncSuperAdminCommand extends Command
{
    protected $signature = 'itqan:sync-super-admin';

    protected $description = 'Create or update the single super admin user from .env values';

    public function handle(): int
    {
        $nationalId = env('SUPER_ADMIN_NATIONAL_ID');
        $password   = env('SUPER_ADMIN_PASSWORD');

        if (empty($nationalId) || empty($password)) {
            $this->error('SUPER_ADMIN_NATIONAL_ID and SUPER_ADMIN_PASSWORD must be set in .env');
            return self::FAILURE;
        }

        $user    = User::withTrashed()->where('is_super_admin', true)->first() ?? new User();
        $existed = $user->exists;

        // forceFill bypasses the Fillable allowlist — appropriate here because the
        // command is privileged and must set is_super_admin / role explicitly.
        $user->forceFill([
            'national_id'    => $nationalId,
            'first_name'     => 'أحمد',
            'family_name'    => 'أبو عبادة',
            'gender'         => 'male',
            'password_hash'  => Hash::make($password),
            'role'           => UserRole::SuperAdmin->value,
            'is_super_admin' => true,
            'is_active'      => true,
        ]);

        if ($existed && $user->trashed()) {
            $user->restore();
        }

        $user->save();

        $this->info($existed
            ? "Super admin updated: {$nationalId}"
            : "Super admin created: {$nationalId}"
        );

        return self::SUCCESS;
    }
}
