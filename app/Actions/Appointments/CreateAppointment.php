<?php

namespace App\Actions\Appointments;

use App\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateAppointment
{
    public function handle(
        int $customerId,
        int $serviceId,
        string $localStart,
    ): Appointment {
        Validator::make(
            ['starts_at' => $localStart],
            ['starts_at' => ['required', 'date_format:Y-m-d\TH:i']],
        )->validate();

        $customer = Customer::query()->findOrFail($customerId);
        $service = Service::query()->findOrFail($serviceId);

        $localStartsAt = CarbonImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $localStart,
            config('business.timezone'),
        );

        if (
            $localStartsAt === null
            || $localStartsAt->format('Y-m-d\TH:i') !== $localStart
        ) {
            throw ValidationException::withMessages([
                'starts_at' => 'Enter a valid appointment time in the company timezone.',
            ]);
        }

        $startsAt = $localStartsAt->utc();

        $endsAt = $startsAt->addMinutes($service->duration_minutes);
        $hasOverlap = Appointment::query()
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'This time overlaps an existing appointment.',
            ]);
        }

        $appointment = new Appointment;
        $appointment->customer_id = $customer->id;
        $appointment->service_id = $service->id;

        $appointment->service_name = $service->name;
        $appointment->service_price = $service->price;
        $appointment->service_duration_minutes = $service->duration_minutes;

        $appointment->starts_at = $startsAt;
        $appointment->ends_at = $endsAt;
        $appointment->status = AppointmentStatus::Scheduled->value;

        $appointment->save();

        return $appointment;
    }
}
