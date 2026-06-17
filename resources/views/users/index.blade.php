<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            User Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">System Users</h3>

                    <a href="{{ route('users.create') }}"
                       style="background-color:#2563eb; color:white; padding:10px 16px; border-radius:6px; font-weight:600; text-decoration:none;">
                        Create User
                    </a>
                </div>

                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 text-center">Name</th>
                            <th class="p-2 text-center">Email</th>
                            <th class="p-2 text-center">Role</th>
                            <th class="p-2 text-center">Created</th>
                            <th class="p-2 text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-t" style="transition:background-color .15s ease;" onmouseover="this.style.backgroundColor='#eff6ff'" onmouseout="this.style.backgroundColor='white'">
                                <td class="p-2 text-center">{{ $user->name }}</td>
                                <td class="p-2 text-center">{{ $user->email }}</td>
                                <td class="p-2 text-center">{{ $user->role?->name ?? 'No Role' }}</td>
                                <td class="p-2 text-center">{{ $user->created_at?->format('m/d/Y') }}</td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('users.edit', $user) }}"
                                           style="background-color:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fde68a; box-shadow:0 2px 6px rgba(146,64,14,.08); text-decoration:none; font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                                            Edit
                                        </a>

                                        @if ($user->id !== auth()->id())
                                            <button type="button"
                                                    onclick="openUserDeleteModal('{{ route('users.destroy', $user) }}', '{{ $user->name }}')"
                                                    style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>

    <div id="userDeleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:white; width:420px; max-width:95vw; border-radius:12px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.25);">
            <h2 style="font-size:20px; font-weight:700; margin-bottom:10px; color:#111827;">
                Confirm User Deletion
            </h2>

            <p style="color:#4b5563; margin-bottom:8px;">
                You are about to delete this user:
            </p>

            <p id="userDeleteName" style="font-weight:700; color:#111827; margin-bottom:20px;"></p>

            <p style="color:#4b5563; margin-bottom:24px;">
                This action cannot be undone. The user will no longer be able to access the JCCS Schedule Manager.
            </p>

            <form id="userDeleteForm" method="POST">
                @csrf
                @method('DELETE')

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button"
                            onclick="closeUserDeleteModal()"
                            style="background:#e5e7eb; color:#111827; padding:8px 14px; border-radius:6px; font-weight:600;">
                        Cancel
                    </button>

                    <button type="submit"
                            style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                        Yes, Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUserDeleteModal(actionUrl, userName) {
            document.getElementById('userDeleteForm').setAttribute('action', actionUrl);
            document.getElementById('userDeleteName').textContent = userName;
            document.getElementById('userDeleteModal').style.display = 'flex';
        }

        function closeUserDeleteModal() {
            document.getElementById('userDeleteModal').style.display = 'none';
        }
    </script>