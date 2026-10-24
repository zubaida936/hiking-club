<?php

namespace App\Http\Requests\Site;

use App\Domain\Contact\Data\ContactMessageData;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'regex:'.Phone::PATTERN],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            // Honeypot: hidden from people, bots fill it in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number, e.g. +961 70 123 456.',
            'message.min' => 'Please write a little more so we can help.',
        ];
    }

    public function toData(): ContactMessageData
    {
        return new ContactMessageData(
            name: $this->string('name')->squish()->toString(),
            email: strtolower($this->string('email')->trim()->toString()),
            phone: Phone::normalize($this->input('phone')),
            message: $this->string('message')->trim()->toString(),
        );
    }
}
