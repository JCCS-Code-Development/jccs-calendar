

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create User
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                            <input type="text"
                                   name="name"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email"
                                   name="email"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                            <select name="role_id" class="w-full border-gray-300 rounded" required>
                                <option value="" disabled selected hidden>Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Temporary Password</label>
                            <input type="password"
                                   name="password"
                                   class="w-full border-gray-300 rounded"
                                   required>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <a href="{{ route('users.index') }}"
                           style="background-color:#e5e7eb; color:#111827; padding:10px 16px; border-radius:6px; text-decoration:none; font-weight:600;">
                            Cancel
                        </a>

                        <button type="submit"
                                style="background-color:#2563eb; color:white; padding:10px 16px; border-radius:6px; font-weight:600;">
                            Create User
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>