<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $users = User::with('role')->orderBy('name')->get();

        return response()->json($users->map(fn ($u) => $this->formatUser($u)));
    }

    public function show(User $user)
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $user->load('role');

        return response()->json($this->formatUser($user));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'role_id'  => $validated['role_id'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->load('role');

        return response()->json($this->formatUser($user), 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->load('role');

        return response()->json($this->formatUser($user));
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()?->canManageUsers(), 403);

        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot delete yourself.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'role_id' => $user->role_id,
            'role'    => $user->role?->name,
        ];
    }
}
