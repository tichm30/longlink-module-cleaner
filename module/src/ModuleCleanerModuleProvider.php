<?php

namespace Modules\ModuleCleaner;

use App\Support\Modules\AbstractModuleRuntimeProvider;
use App\Support\Navigation\MenuRegistry;
use Illuminate\Support\Facades\Route;

class ModuleCleanerModuleProvider extends AbstractModuleRuntimeProvider
{
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

    public function boot(): void
    {
        //
    }
}
