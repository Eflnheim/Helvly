<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'agent',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'requester',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
