<?php

namespace App\Actions\Appointments;

use App\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ChangeAppointmentStatus
{
    public function handle(Appointment $appointment, AppointmentStatus $status): Appointment
    {
        try {
            return Cache::lock('appointments:schedule', 60)->block(5, function () use ($appointment, $status): Appointment {
                $booking = Appointment::query()->findOrFail($appointment->id);
                if ($booking->status !== AppointmentStatus::Scheduled || $status === AppointmentStatus::Scheduled) {
                    throw ValidationException::withMessages(['status' => 'Only scheduled appointments can be cancelled or completed.']);
                }
                $booking->status = $status;
                $booking->save();

                return $booking;
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['status' => 'The schedule is busy. Please try again.']);
        }
    }
}
