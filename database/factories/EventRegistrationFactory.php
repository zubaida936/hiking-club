<?php

namespace Database\Factories;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->published(),
            'reference' => Str::upper(Str::random(8)),
            'token' => Str::random(64),
            'status' => RegistrationStatus::Confirmed,
            'name' => fake()->name(),
            'phone' => '+9617'.fake()->unique()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'pickup_point' => 'Beirut - Jnah',
            'pickup_time' => '07:30:00',
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '+9613'.fake()->numerify('######'),
            'notes' => null,
            'terms_accepted_at' => now(),
        ];
    }

    /**
     * Link each booking to the hiker with its phone number, as the booking flow does.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (EventRegistration $registration) {
            if ($registration->hiker_id === null) {
                $hiker = Hiker::firstOrCreate(['phone' => $registration->phone], ['name' => $registration->name, 'email' => $registration->email]);
                $registration->update(['hiker_id' => $hiker->id]);
            }
        });
    }

    public function pending(): static
    {
        return $this->state(['status' => RegistrationStatus::Pending]);
    }

    public function waitlisted(): static
    {
        return $this->state(['status' => RegistrationStatus::Waitlisted]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => RegistrationStatus::Cancelled]);
    }
}
