<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        if (!$adminRoleId) {
            throw new RuntimeException('Admin role not found.');
        }

        $name = env('ADMIN_NAME');
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!$name || !$email || !$password) {
            throw new RuntimeException(
                'Admin credentials must be configured in the .env file.'
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role_id' => $adminRoleId,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
