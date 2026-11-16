<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            if (! Schema::hasColumn('customers', 'login_code')) {
                $t->string('login_code', 8)->nullable()->unique()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            if (Schema::hasColumn('customers', 'login_code')) {
                $t->dropUnique(['login_code']);
                $t->dropColumn('login_code');
            }
        });
    }
};
