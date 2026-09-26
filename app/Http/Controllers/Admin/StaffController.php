<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Only an admin can create another admin - a manager granting the
        // top role to someone (including themselves via a crafted request)
        // would be a privilege-escalation hole, since this route only
        // requires role:admin|manager.
        abort_if(
            $data['role'] === Role::Admin->value && ! Auth::user()->hasRole(Role::Admin->value),
            403,
            'Only an admin can grant the admin role.'
        );

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

        // Privilege-escalation / lockout guards - this route only requires
        // role:admin|manager, so without these a manager could promote
        // themselves (or anyone) to admin, demote/deactivate an existing
        // admin, or deactivate their own account by mistake.
        $actingUser = Auth::user();
        $isAdmin = $actingUser->hasRole(Role::Admin->value);

        abort_if(
            ! $isAdmin && ($data['role'] === Role::Admin->value || $staffMember->hasRole(Role::Admin->value)),
            403,
            'Only an admin can manage admin accounts.'
        );

        abort_if(
            $staffMember->is($actingUser) && ($data['role'] !== Role::Admin->value || ! ($data['is_active'] ?? false)),
            403,
            'You cannot change your own role or deactivate your own account.'
        );

        $staffMember->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            // Matches admin.staff.show's hidden is_active=0 input ahead of the
            // checkbox (standard unchecked-checkbox trick) - default to
            // inactive, not active, if the field is missing entirely (e.g. a
            // raw API call that skips the hidden field the Blade form sends).
            'is_active' => $data['is_active'] ?? false,
        ]);

        $staffMember->syncRoles([$data['role']]);

        return back()->with('status', 'Staff member updated.');
    }

    public function destroy(User $staffMember): RedirectResponse
    {
        $actingUser = Auth::user();

        abort_if($staffMember->is($actingUser), 403, 'You cannot delete your own account.');

        abort_if(
            $staffMember->hasRole(Role::Admin->value) && ! $actingUser->hasRole(Role::Admin->value),
            403,
            'Only an admin can remove an admin account.'
        );

        $staffMember->delete();

        return back()->with('status', 'Staff member removed.');
    }
}
