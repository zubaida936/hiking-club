<x-admin.layout title="Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $label => $value)
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>
</x-admin.layout>
