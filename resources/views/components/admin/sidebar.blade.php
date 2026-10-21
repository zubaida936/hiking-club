@php
    use App\Domain\Identity\Enums\Permission;

    // Each module registers its entry here; items whose route does not exist yet are hidden.
    $items = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'can' => Permission::AccessAdmin],
        ['label' => 'Events', 'route' => 'admin.events.index', 'active' => 'admin.events.*', 'can' => Permission::ManageEvents],
        ['label' => 'Staff & roles', 'route' => 'admin.staff.index', 'active' => 'admin.staff.*', 'can' => Permission::ManageStaff],
    ];
@endphp

<nav class="mt-4 space-y-1 px-3">
    @foreach ($items as $item)
        @continue(! Route::has($item['route']))
        @can($item['can']->value)
            <a href="{{ route($item['route']) }}"
               @class([
                   'block rounded-md px-3 py-2 text-sm font-medium',
                   'bg-slate-800 text-white' => request()->routeIs($item['active']),
                   'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs($item['active']),
               ])>
                {{ $item['label'] }}
            </a>
        @endcan
    @endforeach
</nav>
