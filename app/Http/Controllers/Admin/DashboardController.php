<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'Staff users' => User::staff()->count(),
                'Active staff' => User::staff()->where('is_active', true)->count(),
            ],
        ]);
    }
}
