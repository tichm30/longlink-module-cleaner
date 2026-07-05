<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;

class DependencyGraphService
{
    private const PROTECTED_MODULE_KEYS = [
        'licensing',
        'email_signatures',
        'embedded_login_gateway',
        'module_generator',
        'module_cleaner',
    ];

    /**
     * @return array<string, mixed>
     */
    public function graphFor(AddonModule $module): array
    {
        $modules = AddonModule::query()->get();
        $knownKeys = $modules->pluck('key')->map(fn (mixed $key): string => (string) $key)->filter()->values()->all();
        $outgoing = $this->dependencyKeysFor($module, $knownKeys);
        $incoming = [];
        $edges = [];

        foreach ($outgoing as $dependsOn) {
            $edges[] = $this->edge((string) $module->key, $dependsOn, 'outgoing');
        }

        foreach ($modules as $candidate) {
            if ((string) $candidate->key === (string) $module->key) {
                continue;
            }

            $candidateDependencies = $this->dependencyKeysFor($candidate, $knownKeys);
            if (in_array((string) $module->key, $candidateDependencies, true)) {
                $incoming[] = (string) $candidate->key;
                $edges[] = $this->edge((string) $candidate->key, (string) $module->key, 'incoming');
            }
        }

        $selfDestruct = $this->selfDestructCheck($module, $incoming);

        return [
            'module_key' => (string) $module->key,
            'incoming' => array_values(array_unique($incoming)),
            'outgoing' => array_values(array_unique($outgoing)),
            'edges' => $edges,
            'blocking_count' => count(array_unique($incoming)),
            'has_blocking_dependencies' => $incoming !== [],
            'self_destruct' => $selfDestruct,
        ];
    }

    /**
     * @param array<int, string>|null $incoming
     * @return array<string, mixed>
     */
    public function selfDestructCheck(AddonModule $module, ?array $incoming = null): array
    {
        $incoming ??= $this->graphFor($module)['incoming'] ?? [];
        $protected = in_array((string) $module->key, self::PROTECTED_MODULE_KEYS, true);
        $blocked = $protected || $incoming !== [];

        return [
            'module_key' => (string) $module->key,
            'status' => $blocked ? 'blocked' : 'allowed_for_host_handoff',
            'reason' => $protected
                ? 'Protected module cannot be self-destructed.'
                : ($incoming !== [] ? 'Dependent modules still reference this module.' : 'No dependent module references detected.'),
            'protected' => $protected,
            'dependent_modules' => array_values(array_unique($incoming)),
        ];
    }

    /**
     * @param array<int, string> $knownKeys
     * @return array<int, string>
     */
    private function dependencyKeysFor(AddonModule $module, array $knownKeys): array
    {
        $payloads = [
            data_get($module->metadata ?: [], 'ownership_snapshot.dependencies', []),
            data_get($module->metadata ?: [], 'dependencies', []),
            data_get($module->metadata ?: [], 'module_json.dependencies', []),
            data_get($module->metadata ?: [], 'manifest.dependencies', []),
            $this->manifestDependencies($module, $knownKeys),
        ];

        return collect($payloads)
            ->flatMap(fn (mixed $payload): array => $this->collectDependencyKeys($payload, $knownKeys))
            ->reject(fn (string $key): bool => $key === (string) $module->key)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function manifestDependencies(AddonModule $module, array $knownKeys): array
    {
        $path = is_string($module->manifest_path) ? trim($module->manifest_path) : '';
        $absolute = $path !== '' && str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : ($path !== '' ? base_path(trim($path, '/')) : '');

        if ($absolute === '' || ! is_file($absolute)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($absolute), true);

        return is_array($manifest) ? $this->collectDependencyKeys($manifest['dependencies'] ?? [], $knownKeys) : [];
    }

    /**
     * @param array<int, string> $knownKeys
     * @return array<int, string>
     */
    private function collectDependencyKeys(mixed $payload, array $knownKeys): array
    {
        if (is_string($payload)) {
            return $knownKeys === [] || in_array($payload, $knownKeys, true) ? [$payload] : [];
        }

        if (! is_array($payload)) {
            return [];
        }

        $keys = [];
        foreach ($payload as $key => $value) {
            if (is_string($key) && ($knownKeys === [] || in_array($key, $knownKeys, true))) {
                $keys[] = $key;
            }

            $keys = array_merge($keys, $this->collectDependencyKeys($value, $knownKeys));
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, mixed>
     */
    private function edge(string $moduleKey, string $dependsOn, string $source): array
    {
        return [
            'module_key' => $moduleKey,
            'depends_on_module_key' => $dependsOn,
            'dependency_type' => 'runtime',
            'source' => $source,
            'is_blocking' => true,
            'metadata' => [],
        ];
    }
}
