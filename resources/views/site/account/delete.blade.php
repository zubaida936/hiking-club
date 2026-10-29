<x-site.auth-card title="Delete my account" intro="This can't be undone.">
    <div class="space-y-3 text-slate-700">
        <p>Deleting your account:</p>
        <ul class="list-disc space-y-1 pl-5">
            <li>deletes your login, your points, your rewards and your X Events application;</li>
            <li>removes your name, phone, email and emergency contact from your past bookings and shop orders (we keep them without your details for the club's records);</li>
            <li>removes you from the leaderboard and the newsletter;</li>
            <li>logs you out on the website and in the app.</li>
        </ul>
        <p class="text-sm text-slate-500">You can't delete your account while you have upcoming bookings or open shop orders: cancel them first, or message us on WhatsApp.</p>
    </div>

    <form method="POST" action="{{ route('account.delete.destroy') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <x-site.password-field label="Your password, to confirm" />
        <button class="w-full rounded-full bg-red-600 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-red-700">Delete my account</button>
    </form>

    <p class="mt-6 text-center">
        <a href="{{ route('account') }}" class="font-semibold text-brand-700 hover:underline">Keep my account</a>
    </p>
</x-site.auth-card>
