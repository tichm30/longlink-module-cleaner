<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

return static function (): void {

$root = module_repo_root();
$controllerSource = (string) file_get_contents(module_source_root().'/src/Http/Controllers/ModuleCleanerController.php');
$registryView = (string) file_get_contents(module_source_root().'/resources/views/admin/registry.blade.php');
$messages = (string) file_get_contents(module_source_root().'/resources/lang/en/messages.php');

foreach ([
    'LengthAwarePaginator' => 'Registry must paginate server-side.',
    '$request->query(\'q\'' => 'Registry must read the search term from the request query.',
    'Str::contains($haystack, $needle)' => 'Registry must filter using server-side search.',
    '$modules->forPage($page, $perPage)' => 'Registry must slice the result set before rendering.',
] as $needle => $message) {
    module_assert(str_contains($controllerSource, $needle), $message);
}

foreach ([
    '<x-list-toolbar' => 'Registry must use the host shared listing toolbar.',
    'data-listing-toolbar="module-cleaner-registry"' => 'Registry toolbar must expose a listing gate marker.',
    'name="q"' => 'Registry search input must submit a query-string q parameter.',
    'data-listing-table="module-cleaner-registry"' => 'Registry table must expose a listing gate marker.',
    '<x-data-table-pagination' => 'Registry must use the host shared pagination component.',
    'data-listing-pagination="module-cleaner-registry"' => 'Registry pagination must expose a listing gate marker.',
] as $needle => $message) {
    module_assert(str_contains($registryView, $needle), $message);
}

module_assert(! str_contains($registryView, '{{ $module->key }}'), 'Registry list rows must not expose the snake_case module key as a visible primary label.');

foreach (['search', 'search_action', 'search_placeholder', 'pagination'] as $key) {
    module_assert(str_contains($messages, "'".$key."'"), 'Registry listing translation must define '.$key.'.');
}

echo "RegistryListingStandardTest passed\n";
};
