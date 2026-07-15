<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ServiceCategorySeeder::class);

        $vendors = [
            [
                'name' => 'استوديو اللحظة',
                'email' => 'vendor.photo@eventak.com',
                'phone' => '0911111111',
                'category' => 'التصوير والفيديو',
                'services' => [
                    ['name' => 'باقة تصوير كاملة (فيديو + صور)', 'price' => 500],
                    ['name' => 'باقة تصوير صور فقط', 'price' => 250],
                ],
            ],
            [
                'name' => 'DJ Karim',
                'email' => 'vendor.dj@eventak.com',
                'phone' => '0911111112',
                'category' => 'الديجي والموسيقى',
                'services' => [
                    ['name' => 'دي جي + نظام صوت وإضاءة', 'price' => 300],
                ],
            ],
            [
                'name' => 'بوفيه الضيافة الذهبية',
                'email' => 'vendor.buffet@eventak.com',
                'phone' => '0911111113',
                'category' => 'البوفيه والضيافة',
                'services' => [
                    ['name' => 'بوفيه فاخر للقاعة', 'price' => 1500],
                    ['name' => 'بوفيه اقتصادي للقاعة', 'price' => 900],
                ],
            ],
            [
                'name' => 'لمسات ديكور',
                'email' => 'vendor.decor@eventak.com',
                'phone' => '0911111114',
                'category' => 'التنسيق والديكور',
                'services' => [
                    ['name' => 'تنسيق وديكور القاعة', 'price' => 400],
                ],
            ],
        ];

        foreach ($vendors as $vendorData) {
            $category = ServiceCategory::where('name', $vendorData['category'])->first();

            $vendor = User::firstOrCreate(
                ['email' => $vendorData['email']],
                [
                    'name' => $vendorData['name'],
                    'phone' => $vendorData['phone'],
                    'role' => 'vendor',
                    'vendor_category_id' => $category?->id,
                ]
            );

            foreach ($vendorData['services'] as $serviceData) {
                Service::firstOrCreate(
                    ['vendor_id' => $vendor->id, 'name' => $serviceData['name']],
                    [
                        'category_id' => $category?->id,
                        'description' => $serviceData['name'],
                        'price' => $serviceData['price'],
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
