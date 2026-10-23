<?php

namespace App\Http\Requests\Site;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Registrations\Data\RegistrationData;
use App\Models\Event;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Events without a public page don't exist as far as visitors know (checked before validating).
     */
    public function authorize(): bool
    {
        abort_unless($this->event()->isVisibleOnSite(), 404);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->event();
        $meetingPointIds = $event->meetingPoints()->pluck('id')->all();

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'meeting_point_id' => [$meetingPointIds === [] ? 'nullable' : 'required', 'integer', Rule::in($meetingPointIds)],
            'emergency_contact_name' => ['required', 'string', 'max:120'],
            'emergency_contact_phone' => ['required', 'string', 'regex:'.Phone::PATTERN, 'different:phone'],
            'notes' => ['nullable', 'string', 'max:500'],
            'referrer_phone' => ['nullable', 'string', 'regex:'.Phone::PATTERN, 'different:phone'],
            // Paid hikes: how the hiker will pay (only the methods offered right now).
            'payment_method' => $event->fee_cents > 0 && PaymentMethod::available() !== []
                ? ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::available()))]
                : ['nullable'], // free hike: ignored
            'level_acknowledged' => [$event->requiresLevelAcknowledgement() ? 'accepted' : 'nullable'],
            'terms' => ['accepted'],
            // Honeypot: hidden from people, bots fill it in.
            'website' => ['prohibited'],
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
            'emergency_contact_phone.different' => 'Your emergency contact needs a different phone number than yours.',
            'referrer_phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
            'referrer_phone.different' => 'Enter the phone number of the friend who invited you, not yours.',
            'meeting_point_id.required' => 'Choose where we pick you up.',
            'level_acknowledged.accepted' => 'Please confirm that you meet the level requirements for this hike.',
            'terms.accepted' => 'Please accept the terms to book your seat.',
            'payment_method.required' => 'Choose how you will pay.',
        ];
    }

    public function toData(): RegistrationData
    {
        $email = strtolower($this->string('email')->trim()->toString());

        return new RegistrationData(
            name: $this->string('name')->squish()->toString(),
            phone: (string) Phone::normalize($this->input('phone')),
            email: $email !== '' ? $email : null,
            meetingPointId: $this->filled('meeting_point_id') ? $this->integer('meeting_point_id') : null,
            emergencyContactName: $this->string('emergency_contact_name')->squish()->toString(),
            emergencyContactPhone: (string) Phone::normalize($this->input('emergency_contact_phone')),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
            levelAcknowledged: $this->boolean('level_acknowledged'),
            referrerPhone: Phone::normalize($this->input('referrer_phone')),
            paymentMethod: PaymentMethod::tryFrom((string) $this->input('payment_method')),
        );
    }

    private function event(): Event
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $event;
    }
}
