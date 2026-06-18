<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'event_subtype')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('event_subtype')->nullable()->after('event_type_id');
            });
        }

        if (! Schema::hasColumn('events', 'details')) {
            Schema::table('events', function (Blueprint $table) {
                $table->json('details')->nullable()->after('event_subtype');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'details')) {
                $table->dropColumn('details');
            }

            if (Schema::hasColumn('events', 'event_subtype')) {
                $table->dropColumn('event_subtype');
            }
        });
    }
};