<?php

namespace Modules\ModuleCleaner;

use App\Support\Modules\AbstractModuleRuntimeProvider;
use App\Support\Navigation\MenuRegistry;
use App\Support\SampleData\SampleDataProviderContract;
use App\Support\SampleData\SeedsSampleDataTables;
use Illuminate\Support\Facades\Route;
use Modules\ModuleCleaner\Settings\ModuleCleanerSettingsCatalog;

class ModuleCleanerModuleProvider extends AbstractModuleRuntimeProvider implements SampleDataProviderContract
{
    use SeedsSampleDataTables;

    public function moduleKey(): string
    {
        return 'module_cleaner';
    }

    public function permissions(): array
    {
        return [
            'module_cleaner.view' => ['group' => 'system', 'super_admin_only' => true],
            'module_cleaner.plan' => ['group' => 'system', 'super_admin_only' => true],
            'module_cleaner.purge' => ['group' => 'system', 'super_admin_only' => true],
        ];
    }

    public function menu(): array
    {
        return [
            MenuRegistry::addonRoute(
                'module_cleaner',
                'platform_tools.module_cleaner',
                'module_cleaner::messages.navigation.cleaner',
                'admin.module-cleaner.index',
                'broom',
                'module_cleaner.view',
                ['admin.module-cleaner.*'],
                'platform_tools',
                '',
                30,
            ),
        ];
    }

    public function settings(): array
    {
        return app(ModuleCleanerSettingsCatalog::class)->definitions();
    }

    public function registerRoutes(): void
    {
        $routes = __DIR__.'/../routes/web.php';

        if (is_file($routes) && ! Route::has('admin.module-cleaner.index')) {
            require $routes;
        }
    }

    public function policies(): array
    {
        return [];
    }

    public function translationsPath(): ?string
    {
        return is_dir(__DIR__.'/../resources/lang') ? __DIR__.'/../resources/lang' : null;
    }

    public function viewsPath(): ?string
    {
        return is_dir(__DIR__.'/../resources/views') ? __DIR__.'/../resources/views' : null;
    }

    protected function sampleDataTables(): array
    {
        return [
            [
                'table' => 'module_cleaner_cleanup_logs',
                'label' => 'Sample Cleaner Cleanup Logs',
                'description' => 'Sample dry-run log rows for Cleaner dashboard and audit demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.cleanup_log.dry_run',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'module_version' => '0.1.0',
                            'action' => 'dry_run',
                            'status' => 'planned',
                            'tables_planned' => ['sample_addon_records'],
                            'files_planned' => ['modules/sample_addon'],
                            'settings_planned' => ['sample_addon.enabled'],
                            'permissions_planned' => ['sample_addon.view'],
                            'navigation_planned' => ['sample_addon.dashboard'],
                            'storage_planned' => ['modules/sample_addon'],
                            'packages_planned' => ['longlink-sample-addon-0.1.0.zip'],
                            'module_files_planned' => ['sample_addon/module.json'],
                            'size_freed_bytes' => 0,
                            'backup_ref' => 'sample-cleaner-backup',
                            'details' => [
                                'sample_record_key' => 'sample.module_cleaner.cleanup_log.dry_run',
                                'purpose' => 'Sample dry-run cleanup evidence for demo environments.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_cleanup_plans',
                'label' => 'Sample Cleaner Cleanup Plans',
                'description' => 'Sample persisted dry-run plans for Cleaner coordination demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.cleanup_plan.sample_addon',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'module_version' => '0.1.0',
                            'status' => 'prepared',
                            'surfaces' => ['tables', 'settings', 'permissions'],
                            'plan_payload' => ['dry_run' => true, 'sample_record_key' => 'sample.module_cleaner.cleanup_plan.sample_addon'],
                            'dependency_graph' => ['incoming' => [], 'outgoing' => []],
                            'orphan_snapshot' => [],
                            'backup_required' => true,
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_backup_sets',
                'label' => 'Sample Cleaner Backup Sets',
                'description' => 'Sample backup-set records for Cleaner restore-plan demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.backup.sample_addon',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'module_version' => '0.1.0',
                            'backup_ref' => 'sample-addon-backup',
                            'relative_path' => 'module_cleaner/backups/sample_addon/sample',
                            'status' => 'created',
                            'manifest_payload' => ['sample_record_key' => 'sample.module_cleaner.backup.sample_addon'],
                            'files' => ['manifest.json'],
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_quarantine_items',
                'label' => 'Sample Cleaner Quarantine Items',
                'description' => 'Sample quarantine records for Cleaner demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.quarantine.sample_addon',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'item_type' => 'module_files',
                            'source_path' => 'modules/sample_addon',
                            'quarantine_path' => 'module_cleaner/quarantine/sample_addon/sample/module_files/sample_addon',
                            'status' => 'copied',
                            'bytes' => 0,
                            'metadata' => ['sample_record_key' => 'sample.module_cleaner.quarantine.sample_addon'],
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_orphan_candidates',
                'label' => 'Sample Cleaner Orphan Candidates',
                'description' => 'Sample orphan candidate rows for review demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.orphan.sample_addon',
                        'attributes' => [
                            'table_name' => 'sample_addon_legacy_rows',
                            'confidence' => 'medium',
                            'status' => 'pending_review',
                            'row_count' => 0,
                            'evidence' => ['sample_record_key' => 'sample.module_cleaner.orphan.sample_addon'],
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_dependency_edges',
                'label' => 'Sample Cleaner Dependency Edges',
                'description' => 'Sample dependency graph rows for Cleaner demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.dependency.sample_addon',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'depends_on_module_key' => 'sample_core',
                            'dependency_type' => 'runtime',
                            'source' => 'sample',
                            'is_blocking' => true,
                            'metadata' => ['sample_record_key' => 'sample.module_cleaner.dependency.sample_addon'],
                        ],
                    ],
                ],
            ],
            [
                'table' => 'module_cleaner_self_destruct_checks',
                'label' => 'Sample Cleaner Self-Destruct Checks',
                'description' => 'Sample self-destruct guard checks for Cleaner demos.',
                'rows' => [
                    [
                        'record_key' => 'sample.module_cleaner.self_destruct.sample_addon',
                        'attributes' => [
                            'module_key' => 'sample_addon',
                            'status' => 'allowed_for_host_handoff',
                            'reason' => 'Sample module has no dependent module references.',
                            'checks' => ['sample_record_key' => 'sample.module_cleaner.self_destruct.sample_addon'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function boot(): void
    {
        //
    }
}
