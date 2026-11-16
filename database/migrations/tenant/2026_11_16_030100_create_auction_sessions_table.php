<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auction_sessions')) {
            return;
        }

        Schema::create('auction_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->text('cookies')->nullable();
            $t->text('local_storage')->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_sessions');
    }
};
