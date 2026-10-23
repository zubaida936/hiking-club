<?php

namespace App\Http\Requests\Admin;

use App\Domain\Registrations\Data\RegistrationDetailsData;
use App\Models\EventRegistration;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var EventRegistration $registration */
        $registration = $this->route('registration');

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'meeting_point_id' => ['nullable', 'integer', Rule::in($registration->event->meetingPoints()->pluck('id')->all())],
            'emergency_contact_name' => ['required', 'string', 'max:120'],
            'emergency_contact_phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'meeting_point_id' => 'pickup point',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
            'emergency_contact_phone.regex' => 'Enter a valid phone number, e.g. +961 3 123 456.',
        ];
    }

    public function toData(): RegistrationDetailsData
    {
        $email = strtolower($this->string('email')->trim()->toString());

        return new RegistrationDetailsData(
            name: $this->string('name')->squish()->toString(),
            phone: (string) Phone::normalize($this->input('phone')),
            email: $email !== '' ? $email : null,
            meetingPointId: $this->filled('meeting_point_id') ? $this->integer('meeting_point_id') : null,
            emergencyContactName: $this->string('emergency_contact_name')->squish()->toString(),
            emergencyContactPhone: (string) Phone::normalize($this->input('emergency_contact_phone')),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
