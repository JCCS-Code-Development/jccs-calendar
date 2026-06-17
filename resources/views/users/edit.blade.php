<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit User
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="mb-6 bg-red-100 text-red-700 p-4 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                            <select name="role_id" class="w-full border-gray-300 rounded" required>
                                @if (!$user->role_id)
                                    <option value="" disabled selected hidden>Select Role</option>
                                @endif
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                            <input type="password"
                                   name="password"
                                   class="w-full border-gray-300 rounded"
                                   placeholder="Leave blank to keep current password">
                            <p class="text-xs text-gray-500 mt-1">
                                Only enter a password if you want to reset this user's password.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <a href="{{ route('users.index') }}"
                           style="background-color:#e5e7eb; color:#111827; padding:10px 16px; border-radius:6px; text-decoration:none; font-weight:600;">
                            Cancel
                        </a>

                        <button type="submit"
                                style="background-color:#2563eb; color:white; padding:10px 16px; border-radius:6px; font-weight:600;">
                            Update User
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>