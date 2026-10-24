@php
    $whatsapp = \App\Support\WhatsApp::link($contact['whatsapp_message']);
    $socialLinks = \App\Support\SocialLinks::all();
@endphp

<x-site.layout title="Contact" description="Questions about a hike, a booking or the club? Message us on WhatsApp, email us or send the contact form. We usually answer within a day.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Questions?</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Contact</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">About a hike, a booking or the club: write to us, we usually answer within a day.</p>
    </section>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-5">
        {{-- Form --}}
        <section class="lg:col-span-3" aria-labelledby="form-heading">
            <h2 id="form-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Send us a message</h2>

            @if (session('status'))
                <p class="mt-6 rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('contact.send') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-site.field name="name" label="Your name" required maxlength="80" autocomplete="name" />
                    <x-site.field name="email" label="Email" type="email" required maxlength="190" autocomplete="email" hint="We answer to this address." />
                </div>
                <x-site.field name="phone" label="Phone / WhatsApp" type="tel" :optional="true" autocomplete="tel" placeholder="+961 70 123 456" />
                <div>
                    <label for="message" class="block text-sm font-semibold text-slate-700">Message</label>
                    <textarea id="message" name="message" rows="6" required maxlength="3000"
                              @error('message') aria-invalid="true" aria-describedby="message-help" @enderror
                              @class([
                                  'mt-1 block w-full rounded-xl border bg-white px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500',
                                  'border-red-400' => $errors->has('message'),
                                  'border-slate-300' => ! $errors->has('message'),
                              ])>{{ old('message') }}</textarea>
                    @error('message') <p id="message-help" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                {{-- Honeypot: hidden from people, bots fill it in --}}
                <div class="hidden" aria-hidden="true">
                    <label for="contact-website">Website</label>
                    <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>
                @error('website') <p class="text-sm text-red-600" role="alert">Something went wrong, please try again.</p> @enderror
                <button type="submit" class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Send message</button>
            </form>
        </section>

        {{-- Details --}}
        <aside class="space-y-4 lg:col-span-2" aria-label="Contact details">
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="block rounded-2xl bg-emerald-50 p-6 ring-1 ring-emerald-200 hover:ring-emerald-500">
                    <p class="font-display font-semibold uppercase tracking-wide text-emerald-800">WhatsApp</p>
                    <p class="mt-1 text-slate-700">The fastest way to reach us. Chat now &rarr;</p>
                </a>
            @endif
            <div class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Phone</p>
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="mt-1 block text-slate-700 hover:text-brand-600">{{ $contact['phone'] }}</a>
            </div>
            <div class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Email</p>
                <a href="mailto:{{ $contact['email'] }}" class="mt-1 block break-all text-slate-700 hover:text-brand-600">{{ $contact['email'] }}</a>
            </div>
            <div class="rounded-2xl p-6 ring-1 ring-slate-200">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Address</p>
                <address class="mt-1 not-italic text-slate-700">{{ $contact['address'] }}</address>
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:text-brand-800">Open in Google Maps &rarr;</a>
            </div>
            @if ($socialLinks)
                <div class="rounded-2xl p-6 ring-1 ring-slate-200">
                    <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Follow us</p>
                    <ul class="mt-3 flex flex-wrap gap-3">
                        @foreach ($socialLinks as $network => [$label, $url])
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}"
                                   class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 hover:bg-accent-500 hover:text-white">
                                    <x-site.social-icon :name="$network" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-site.layout>
