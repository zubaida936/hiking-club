<?php

namespace App\Http\Requests\Site;

use App\Domain\Newsletter\Data\SubscriptionData;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    /**
     * Errors send the visitor back to the form on the homepage.
     */
    protected function getRedirectUrl(): string
    {
        return route('home').'#newsletter';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'required_without:phone', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:'.Phone::PATTERN],
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
            'email.required_without' => 'Enter your email or your phone number.',
            'phone.required_without' => 'Enter your email or your phone number.',
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
        ];
    }

    public function toData(): SubscriptionData
    {
        return SubscriptionData::from($this->input('email'), $this->input('phone'));
    }
}
