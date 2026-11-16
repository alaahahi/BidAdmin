<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $t) {
            if (! Schema::hasColumn('activity_logs', 'user_id')) {
                $t->foreignId('user_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('activity_logs', 'source')) {
                $t->string('source', 20)->nullable()->index()->after('app');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $t) {
            if (Schema::hasColumn('activity_logs', 'user_id')) {
                $t->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('activity_logs', 'source')) {
                $t->dropColumn('source');
            }
        });
    }
};
