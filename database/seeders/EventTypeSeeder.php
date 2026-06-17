<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $eventTypes = [
            ['name' => 'Job', 'color' => '#2563eb'],
            ['name' => 'Appointment', 'color' => '#16a34a'],
            ['name' => 'Call', 'color' => '#f97316'],
            ['name' => 'Meeting', 'color' => '#7c3aed'],
            ['name' => 'Reminder', 'color' => '#dc2626'],
            ['name' => 'Quote Walk-Through', 'color' => '#0891b2'],
        ];

        foreach ($eventTypes as $type) {
            \App\Models\EventType::firstOrCreate(
                ['name' => $type['name']],
                ['color' => $type['color']]
            );
        }
    }
}
