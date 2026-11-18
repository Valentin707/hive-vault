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
        Schema::create('vault_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('folder_id')->nullable()->constrained('vault_folders')->nullOnDelete();
            $table->string('type')->index();
            $table->text('encrypted_title');
            $table->string('title_iv');
            $table->longText('encrypted_payload');
            $table->string('payload_iv');
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_items');
    }
};
