<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([VendorSeeder::class, VenueOwnerSeeder::class]);

        $customer = User::firstOrCreate(
            ['email' => 'customer@eventak.com'],
            [
                'name' => 'أحمد الزبون',
                'phone' => '0933333333',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        $diamondVenue = Venue::where('name->ar', 'قاعة الماسة')->first();
        $pearlVenue = Venue::where('name->ar', 'قاعة اللؤلؤة')->first();

        $photoService = Service::where('name->ar', 'باقة تصوير كاملة (فيديو + صور)')->first();
        $djService = Service::where('name->ar', 'دي جي + نظام صوت وإضاءة')->first();
        $decorService = Service::where('name->ar', 'تنسيق وديكور القاعة')->first();
        $cheapBuffetService = Service::where('name->ar', 'بوفيه اقتصادي للقاعة')->first();

        $this->createEvent(
            customer: $customer,
            venue: $diamondVenue,
            eventName: 'حفل زفاف',
            eventType: 'زفاف',
            date: Carbon::today()->addDays(10),
            startTime: '18:00',
            endTime: '23:00',
            guests: 250,
            status: 'confirmed',
            services: [$photoService, $djService, $decorService],
            withPayment: true,
        );

        $this->createEvent(
            customer: $customer,
            venue: $pearlVenue,
            eventName: 'حفلة تخرج',
            eventType: 'تخرج',
            date: Carbon::today()->addDays(20),
            startTime: '16:00',
            endTime: '21:00',
            guests: 100,
            status: 'pending',
            services: [$cheapBuffetService],
            withPayment: false,
        );
    }

    private function createEvent(
        User $customer,
        Venue $venue,
        string $eventName,
        string $eventType,
        Carbon $date,
        string $startTime,
        string $endTime,
        int $guests,
        string $status,
        array $services,
        bool $withPayment,
    ): void {
        if (Event::where('customer_id', $customer->id)->where('event_name->ar', $eventName)->exists()) {
            return;
        }

        $servicesTotal = collect($services)->sum(fn (Service $service) => $service->price);
        $totalPrice = $venue->price + $servicesTotal;

        $invoice = Invoice::create([
            'venue_price' => $venue->price,
            'services_total' => $servicesTotal,
            'total_amount' => $totalPrice,
            'status' => $withPayment ? 'paid' : 'pending',
        ]);

        $event = Event::create([
            'customer_id' => $customer->id,
            'event_name' => $eventName,
            'event_type' => $eventType,
            'venue_id' => $venue->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'guests_count' => $guests,
            'total_price' => $totalPrice,
            'invoice_id' => $invoice->id,
            'status' => $status,
        ]);

        $invoice->update(['event_id' => $event->id]);

        foreach ($services as $service) {
            $event->services()->attach($service->id, [
                'quantity' => 1,
                'price' => $service->price,
                'status' => 'accepted',
            ]);
        }

        if ($withPayment) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_method' => 'cash',
                'amount' => $totalPrice,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            $event->update(['payment_id' => $payment->id]);
        }
    }
}
