<?php

namespace Database\Seeders;


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
        // 1. إنشاء حساب الأدمن وحفظه في متغير
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@eventak.com', // الإيميل الذي سيدخل به
            'password' => Hash::make('admin12345'), // كلمة المرور
            'role' => 'admin',
            'phone' => '0900000000'
        ]);

        // 2. توليد التوكن الخاص بالأدمن عبر Sanctum بصلاحيات كاملة ['*']
        $token = $admin->createToken('AdminDashboardToken', ['*'])->plainTextToken;

        // 3. طباعة التوكن في التيرمنال بشكل واضح ومميز لتنسخه مباشرة
        $this->command->info("\n========================================================");
        $this->command->warn("🔥 ADMIN TOKEN GENERATED SUCCESSFULLY FOR POSTMAN 🔥");
        $this->command->info("Token: " . $token);
        $this->command->info("========================================================\n");
    }
}
