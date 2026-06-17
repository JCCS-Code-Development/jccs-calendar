<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            JCCS Schedule Calendar
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">
                        Company Calendar
                    </h3>

                    <a href="{{ route('events.create') }}"
                       style="background-color:#2563eb; color:white; padding:10px 16px; border-radius:6px; text-decoration:none;">
                        Create Event
                    </a>
                </div>

                <div id="calendar"></div>

            </div>
        </div>
    </div>
</x-app-layout>