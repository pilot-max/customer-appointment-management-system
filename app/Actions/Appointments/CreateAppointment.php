<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;

class CreateAppointment
{
    public function handle(int $customerId, int $serviceId, string $localStart): Appointment
    {
        return app(SaveAppointment::class)->handle($customerId, $serviceId, $localStart);
    }
}
