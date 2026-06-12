<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_cleaner_cleanup_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('addon_module_id')->nullable()->index();
            $table->string('module_key')->index();
            $table->string('module_version')->nullable();
            $table->string('action')->index();
            $table->string('status')->default('planned')->index();
            $table->json('tables_planned')->nullable();
            $table->json('files_planned')->nullable();
            $table->json('settings_planned')->nullable();
            $table->json('permissions_planned')->nullable();
            $table->unsignedBigInteger('size_freed_bytes')->default(0);
            $table->string('backup_ref')->nullable();
            $table->json('details')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_cleaner_cleanup_logs');
    }
};
