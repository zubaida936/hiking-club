<x-site.layout title="Delete your account" description="How to delete your Hiking Club account and what happens to your data.">
    <section class="bg-brand-800 px-4 py-12 text-white sm:px-6">
        <div class="mx-auto max-w-3xl">
            <h1 class="font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">Delete your account</h1>
            <p class="mt-3 text-white/85">You can delete your {{ config('app.name') }} account yourself, at any time, in the app or on this website.</p>
        </div>
    </section>

    <div class="mx-auto max-w-3xl space-y-8 px-4 py-12 text-slate-700 sm:px-6">
        @if (session('status'))
            <p class="rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('status') }}</p>
        @endif

        <section>
            <h2 class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">How</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li><strong>In the app:</strong> Account tab → "Delete my account" → enter your password.</li>
                <li><strong>On the website:</strong> <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">log in</a> → My account → "Delete my account" → enter your password.</li>
                <li><strong>Can't log in?</strong> Message us on WhatsApp or email <a href="mailto:{{ config('site.contact.email') }}" class="font-semibold text-brand-700 hover:underline">{{ config('site.contact.email') }}</a> from the phone number or email of your account, and we delete it for you.</li>
            </ul>
            <p class="mt-3">If you still have upcoming bookings or open shop orders, cancel them first (or ask us to).</p>
        </section>

        <section>
            <h2 class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">What is deleted</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li>Your login (password and every app login), your points, your rewards and your X Events application.</li>
                <li>Your name, phone number, email, emergency contact and notes in your past bookings and shop orders, and your delivery address.</li>
                <li>Your newsletter sign-up, and your place on the leaderboard.</li>
            </ul>
        </section>

        <section>
            <h2 class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">What we keep</h2>
            <p class="mt-3">Your past bookings and shop orders stay in our records <strong>without any personal details</strong> ("Deleted member"): which hike or item, the date, the amount paid and the payment method. We need them for the club's accounts and statistics. Nothing in them points to you any more.</p>
        </section>

        <p>The deletion is immediate and can't be undone. You can always book again later as a new hiker.</p>
    </div>
</x-site.layout>
