<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIfMissing('module_cleaner_cleanup_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('addon_module_id')->nullable()->index('mc_plans_addon_module_idx');
            $table->string('module_key', 120)->index('mc_plans_module_key_idx');
            $table->string('module_version', 80)->nullable();
            $table->string('status', 40)->default('prepared')->index('mc_plans_status_idx');
            $table->json('surfaces')->nullable();
            $table->json('plan_payload')->nullable();
            $table->json('dependency_graph')->nullable();
            $table->json('orphan_snapshot')->nullable();
            $table->boolean('backup_required')->default(true);
            $table->string('host_purge_route', 190)->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('mc_plans_actor_idx');
            $table->timestamp('prepared_at')->nullable()->index('mc_plans_prepared_idx');
            $table->timestamps();
        });

        $this->createIfMissing('module_cleaner_backup_sets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('addon_module_id')->nullable()->index('mc_backups_addon_module_idx');
            $table->unsignedBigInteger('cleanup_plan_id')->nullable()->index('mc_backups_plan_idx');
            $table->string('module_key', 120)->index('mc_backups_module_key_idx');
            $table->string('module_version', 80)->nullable();
            $table->string('backup_ref', 190)->unique('mc_backups_ref_unique');
            $table->string('relative_path', 255);
            $table->string('status', 40)->default('created')->index('mc_backups_status_idx');
            $table->json('manifest_payload')->nullable();
            $table->json('files')->nullable();
            $table->json('restore_payload')->nullable();
            $table->timestamp('restore_prepared_at')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('mc_backups_actor_idx');
            $table->timestamps();
        });

        $this->createIfMissing('module_cleaner_quarantine_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('addon_module_id')->nullable()->index('mc_quarantine_addon_module_idx');
            $table->unsignedBigInteger('cleanup_plan_id')->nullable()->index('mc_quarantine_plan_idx');
            $table->unsignedBigInteger('backup_set_id')->nullable()->index('mc_quarantine_backup_idx');
            $table->string('module_key', 120)->index('mc_quarantine_module_key_idx');
            $table->string('item_type', 60)->index('mc_quarantine_item_type_idx');
            $table->text('source_path')->nullable();
            $table->text('quarantine_path')->nullable();
            $table->string('status', 40)->default('copied')->index('mc_quarantine_status_idx');
            $table->unsignedBigInteger('bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('mc_quarantine_actor_idx');
            $table->timestamps();
        });

        $this->createIfMissing('module_cleaner_orphan_candidates', function (Blueprint $table): void {
            $table->id();
            $table->string('table_name', 190)->unique('mc_orphans_table_unique');
            $table->string('confidence', 40)->index('mc_orphans_confidence_idx');
            $table->string('status', 40)->default('pending_review')->index('mc_orphans_status_idx');
            $table->unsignedBigInteger('row_count')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('detected_at')->nullable()->index('mc_orphans_detected_idx');
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('mc_orphans_actor_idx');
            $table->timestamps();
        });

        $this->createIfMissing('module_cleaner_dependency_edges', function (Blueprint $table): void {
            $table->id();
            $table->string('module_key', 120)->index('mc_dep_module_idx');
            $table->string('depends_on_module_key', 120)->index('mc_dep_depends_on_idx');
            $table->string('dependency_type', 60)->default('runtime')->index('mc_dep_type_idx');
            $table->string('source', 120)->default('manifest');
            $table->boolean('is_blocking')->default(true)->index('mc_dep_blocking_idx');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['module_key', 'depends_on_module_key', 'dependency_type'], 'mc_dep_edge_unique');
        });

        $this->createIfMissing('module_cleaner_self_destruct_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('module_key', 120)->index('mc_self_module_idx');
            $table->string('status', 40)->default('blocked')->index('mc_self_status_idx');
            $table->text('reason')->nullable();
            $table->json('checks')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index('mc_self_actor_idx');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_cleaner_self_destruct_checks');
        Schema::dropIfExists('module_cleaner_dependency_edges');
        Schema::dropIfExists('module_cleaner_orphan_candidates');
        Schema::dropIfExists('module_cleaner_quarantine_items');
        Schema::dropIfExists('module_cleaner_backup_sets');
        Schema::dropIfExists('module_cleaner_cleanup_plans');
    }

    private function createIfMissing(string $tableName, \Closure $callback): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, $callback);
    }
};
