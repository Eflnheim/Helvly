<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hardware',
                'description' => 'Masalah yang berkaitan dengan komputer, laptop, printer, dan perangkat keras lainnya.',
            ],
            [
                'name' => 'Software',
                'description' => 'Masalah yang berkaitan dengan aplikasi, sistem operasi, dan kesalahan pada perangkat lunak.',
            ],
            [
                'name' => 'Jaringan',
                'description' => 'Masalah yang berkaitan dengan koneksi internet, Wi-Fi, dan jaringan lokal.',
            ],
            [
                'name' => 'Akun dan Akses',
                'description' => 'Masalah yang berkaitan dengan akun pengguna, kata sandi, dan hak akses.',
            ],
            [
                'name' => 'Lainnya',
                'description' => 'Masalah terkait IT lainnya yang tidak termasuk dalam kategori yang tersedia.',
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
