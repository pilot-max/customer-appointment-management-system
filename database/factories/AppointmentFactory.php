<?php

namespace Database\Factories;

use App\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => CustomerFactory::new(),
            'service_id' => ServiceFactory::new(),
            'service_name' => 'Haircut',
            'service_price' => '30.00',
            'service_duration_minutes' => 60,
            'starts_at' => '2030-01-10 10:00:00',
            'ends_at' => '2030-01-10 11:00:00',
            'status' => AppointmentStatus::Scheduled->value,
        ];
    }
}
