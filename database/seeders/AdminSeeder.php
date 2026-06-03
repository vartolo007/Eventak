<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@eventak.com', // الإيميل الذي سيدخل به
            'password' => Hash::make('admin12345'), // كلمة المرور
            'role' => 'admin',
            'phone' => '0900000000'
        ]);
    }
}
