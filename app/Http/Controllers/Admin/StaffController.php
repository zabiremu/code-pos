<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::with(['branch', 'roles'])->orderBy('name')->paginate(20);
        $branches = Branch::orderBy('name')->get();

        return view('admin.staff.index', [
            'staff' => $staff,
            'branches' => $branches,
            'roles' => Role::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'role' => ['required', 'in:'.implode(',', Role::values())],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        return back()->with('status', 'Staff member added.');
    }

    /** Full employee profile, its edit form, and their shift history. */
    public function show(User $staffMember): View
    {
        $staffMember->load('branch', 'roles');

        $shifts = $staffMember->shifts()->latest('clock_in')->paginate(15);

        return view('admin.staff.show', [
            'staffMember' => $staffMember,
            'shifts' => $shifts,
            'branches' => Branch::orderBy('name')->get(),
            'roles' => Role::cases(),
        ]);
    }

    public function update(Request $request, User $staffMember): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($staffMember->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'role' => ['required', 'in:'.implode(',', Role::values())],
            'is_active' => ['boolean'],
        ]);

        $staffMember->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $staffMember->syncRoles([$data['role']]);

        return back()->with('status', 'Staff member updated.');
    }

    public function destroy(User $staffMember): RedirectResponse
    {
        $staffMember->delete();

        return back()->with('status', 'Staff member removed.');
    }
}
