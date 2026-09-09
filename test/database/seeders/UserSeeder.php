<?php

namespace Tiny\Test\Database\Seeders;

use Illuminate\Database\Seeder;
use Tiny\Test\Model\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ? updateOrCreate keyed by email so re-running `php test/seed.php`
        // ? never fails on the unique constraint - safe to seed repeatedly.
        for ($i = 1; $i <= 10; $i++) {
            User::updateOrCreate(
                ["email" => "seed.user{$i}@example.com"],
                ["name" => "Seed User {$i}"]
            );
        }
    }
}
