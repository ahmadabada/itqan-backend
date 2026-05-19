<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// One-shot cleanup for the test phase: drops all exam, student, permit, device,
// and merge data; deletes every non-super-admin user; then re-runs the super admin
// sync. recitation_questions, suggested_students, audit_logs, and system_settings
// are preserved (the admin curates the suggestion list independently of test
// runs — wiping it would force re-uploading the autocomplete source).
class WipeTestDataCommand extends Command
{
    protected $signature = 'itqan:wipe-test-data {--force : Skip the confirmation prompt}';

    protected $description = 'Hard-delete all exam/student/device test data. Keeps recitation_questions, super admin, settings.';

    public function handle(): int
    {
        $isProduction = app()->environment('production');

        if ($isProduction && ! $this->option('force')) {
            $this->error('APP_ENV=production. Pass --force to override (this is destructive).');
            return self::FAILURE;
        }

        if ($isProduction) {
            $this->warn('!! Running destructive wipe with --force in APP_ENV=production !!');
        }

        if (! $this->option('force') && ! $this->confirm('This will permanently delete all exams, students, permits, devices, and non-super-admin users. Continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // FK-respecting truncate of the test-domain tables.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'exam_questions',
            'reexam_permits',
            'exams',
            'students',
            'devices',
            'merge_operations',
            'device_commands',
        ] as $table) {
            DB::table($table)->truncate();
            $this->line("  truncated: {$table}");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Delete audit logs for non-super-admin users to prevent FK constraint violations.
        $nonSuperAdminIds = User::withTrashed()->where('is_super_admin', false)->pluck('id');
        if ($nonSuperAdminIds->isNotEmpty()) {
            $deletedLogs = DB::table('audit_logs')->whereIn('user_id', $nonSuperAdminIds)->delete();
            $this->line("  deleted audit_logs for non-super-admins: {$deletedLogs}");
        }

        // Hard-delete non-super-admin users (and their personal access tokens).
        $deleted = User::withTrashed()
            ->where('is_super_admin', false)
            ->forceDelete();
        $this->line("  deleted users (non-super-admin): {$deleted}");

        // Ensure the super admin exists/is up to date with .env.
        $this->call('itqan:sync-super-admin');

        $this->info('Test data wipe complete.');
        return self::SUCCESS;
    }
}
