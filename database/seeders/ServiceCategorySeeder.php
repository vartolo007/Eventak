<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'التصوير والفيديو',
            'الديجي والموسيقى',
            'البوفيه والضيافة',
            'التنسيق والديكور',
        ];

        foreach ($categories as $name) {
            ServiceCategory::firstOrCreate(['name' => $name]);
        }
    }
}
