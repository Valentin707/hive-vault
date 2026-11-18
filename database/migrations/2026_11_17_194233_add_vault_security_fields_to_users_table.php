<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vault_salt')->nullable();
            $table->text('vault_verifier')->nullable();
            $table->unsignedSmallInteger('vault_auto_lock_minutes')->default(15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vault_salt',
                'vault_verifier',
                'vault_auto_lock_minutes',
            ]);
        });
    }
};
