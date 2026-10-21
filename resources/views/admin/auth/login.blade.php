<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ config('app.name') }} CMS</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-full items-center justify-center p-4 antialiased">
    <div class="w-full max-w-sm rounded-xl bg-white p-8 shadow-xl">
        <h1 class="text-xl font-bold uppercase tracking-wide text-slate-900">{{ config('app.name') }} CMS</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to manage the website.</p>

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-admin.input name="email" label="Email" type="email" required autofocus autocomplete="username" />
            <x-admin.input name="password" label="Password" type="password" required autocomplete="current-password" />

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Remember me
            </label>

            <x-admin.button class="w-full">Sign in</x-admin.button>
        </form>
    </div>
</body>
</html>
