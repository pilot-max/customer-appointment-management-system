<?php

namespace App\Actions\Appointments;

use App\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveAppointment
{
    public function handle(int $customerId, int $serviceId, string $localStart, ?Appointment $appointment = null): Appointment
    {
        Validator::make(['starts_at' => $localStart], [
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ])->validate();

        try {
            return Cache::lock('appointments:schedule', 60)->block(5, function () use ($customerId, $serviceId, $localStart, $appointment): Appointment {
                return DB::transaction(function () use ($customerId, $serviceId, $localStart, $appointment): Appointment {
                    $booking = $appointment ? Appointment::query()->findOrFail($appointment->id) : new Appointment;

                    if ($booking->exists && $booking->status !== AppointmentStatus::Scheduled) {
                        throw ValidationException::withMessages(['starts_at' => 'Only scheduled appointments can be edited.']);
                    }

                    $customer = Customer::query()->when($booking->exists && $booking->customer_id === $customerId, fn ($query) => $query->withTrashed())->find($customerId);
                    $service = Service::query()->when($booking->exists && $booking->service_id === $serviceId, fn ($query) => $query->withTrashed())->find($serviceId);

                    if (! $customer) {
                        throw ValidationException::withMessages(['customer_id' => 'Choose an available customer.']);
                    }
                    if (! $service) {
                        throw ValidationException::withMessages(['service_id' => 'Choose an available service.']);
                    }

                    $keepServiceDetails = $booking->exists && $booking->service_id === $serviceId;
                    $duration = $keepServiceDetails ? $booking->service_duration_minutes : $service->duration_minutes;
                    $startsAt = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $localStart, 'UTC');
                    if (! $startsAt || $startsAt->format('Y-m-d\TH:i') !== $localStart) {
                        throw ValidationException::withMessages(['starts_at' => 'Enter a valid appointment date and time.']);
                    }
                    $endsAt = $startsAt->addMinutes($duration);
                    $overlap = Appointment::query()
                        ->when($booking->exists, fn ($query) => $query->whereKeyNot($booking->id))
                        ->where('status', '!=', AppointmentStatus::Cancelled->value)
                        ->where('starts_at', '<', $endsAt->format('Y-m-d H:i:s'))
                        ->where('ends_at', '>', $startsAt->format('Y-m-d H:i:s'))
                        ->exists();

                    if ($overlap) {
                        throw ValidationException::withMessages(['starts_at' => 'This time overlaps an existing appointment.']);
                    }

                    $booking->customer_id = $customer->id;
                    $booking->service_id = $service->id;
                    if (! $keepServiceDetails) {
                        $booking->service_name = $service->name;
                        $booking->service_price = $service->price;
                        $booking->service_duration_minutes = $duration;
                    }
                    $booking->starts_at = $startsAt;
                    $booking->ends_at = $endsAt;
                    $booking->status = AppointmentStatus::Scheduled;
                    $booking->save();

                    return $booking;
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['starts_at' => 'The schedule is busy. Please try again.']);
        }
    }
}
