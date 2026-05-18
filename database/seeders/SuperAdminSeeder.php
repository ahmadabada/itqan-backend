<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

// BR-USR-01: One super admin only. Delegated to the artisan command so .env values
// drive the credentials in both the seed flow and any later password rotation.
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('itqan:sync-super-admin');
        $this->command->getOutput()->write(Artisan::output());
    }
}
