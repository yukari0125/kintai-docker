<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Display the staff list for admins.
     */
    public function index(): View
    {
        $staff = User::query()
            ->where('role', User::ROLE_USER)
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', [
            'staff' => $staff,
        ]);
    }
}
