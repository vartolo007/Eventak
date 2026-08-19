<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owners = [
            [
                'name' => 'مالك قاعة الماسة',
                'email' => 'owner.diamond@eventak.com',
                'phone' => '0922222221',
                'venue' => [
                    'name' => 'قاعة الماسة',
                    'address' => 'دمشق - المزة',
                    'capacity' => 300,
                    'price' => 1000,
                    'description' => 'قاعة أفراح فاخرة بإطلالة بانورامية ومواقف سيارات واسعة.',
                ],
            ],
            [
                'name' => 'مالك قاعة اللؤلؤة',
                'email' => 'owner.pearl@eventak.com',
                'phone' => '0922222222',
                'venue' => [
                    'name' => 'قاعة اللؤلؤة',
                    'address' => 'حلب - الفرقان',
                    'capacity' => 150,
                    'price' => 600,
                    'description' => 'قاعة مناسبة للحفلات الصغيرة والمتوسطة بأسعار اقتصادية.',
                ],
            ],
        ];

        foreach ($owners as $ownerData) {
            $owner = User::firstOrCreate(
                ['email' => $ownerData['email']],
                [
                    'name' => $ownerData['name'],
                    'phone' => $ownerData['phone'],
                    'role' => 'venue_owner',
                ]
            );

            $venueExists = Venue::where('owner_id', $owner->id)
                ->where('name->ar', $ownerData['venue']['name'])
                ->exists();
            if (!$venueExists) {
                Venue::create([
                    'owner_id' => $owner->id,
                    'name' => $ownerData['venue']['name'],
                    'address' => $ownerData['venue']['address'],
                    'capacity' => $ownerData['venue']['capacity'],
                    'price' => $ownerData['venue']['price'],
                    'description' => $ownerData['venue']['description'],
                    'status' => 'active',
                ]);
            }
        }
    }
}
