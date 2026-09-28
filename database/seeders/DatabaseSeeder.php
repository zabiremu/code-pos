<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Dev / live demo only. The web installer runs InstallSeeder instead,
        // so a buyer's install never has these publicly known logins.
        $this->call([
            RolesAndAdminSeeder::class,
            DemoStaffSeeder::class,
            DemoProductSeeder::class,
        ]);
    }
}
