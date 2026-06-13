<x-layouts.app-shell :title="__('module_cleaner::messages.navigation.cleaner')" subtitle="Review install-time ownership snapshots, prepare dry-run cleanup plans, and keep purge execution inside the host safety boundary.">
    <div class="settings-layout">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($latestPlan)
            <x-card
                title="Latest Dry-Run Plan"
                description="The cleaner shows dry-run plans only in this release. Destructive purge execution remains inside the host step-up boundary."
            >
                <div class="data-table-wrap is-key-value-table">
                    <table class="data-table is-key-value-table is-not-sticky-header">
                        <tbody>
                            <tr>
                                <th scope="row">Module</th>
                                <td>{{ $latestPlan['module_key'] ?? 'Unknown' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Mode</th>
                                <td>{{ ($latestPlan['dry_run'] ?? false) ? 'Dry run' : 'Execute' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Surfaces</th>
                                <td>{{ implode(', ', $latestPlan['surfaces'] ?? []) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pill-list">
                    @foreach (($latestPlan['items'] ?? []) as $item)
                        <span class="pill info">
                            {{ $item['surface'] ?? 'surface' }}:
                            {{ $item['action'] ?? 'planned' }}
                            {{ is_array($item['target'] ?? null) ? implode(', ', $item['target']) : ($item['target'] ?? '') }}
                        </span>
                    @endforeach
                </div>
            </x-card>
        @endif

        <x-card
            title="Snapshot Inventory"
            description="Modules without a v1 ownership snapshot are visible, but cannot be purged by this cleaner. The module_cleaner.purge permission is reserved for the future host-guarded purge screen and is not exposed in this release."
        >
            @if ($modules === [])
                <x-empty-state description="No addon modules have been registered yet." />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Status</th>
                                <th>Snapshot</th>
                                <th>Residue Summary</th>
                                <th>Protection</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $entry)
                                @php
                                    $module = $entry['module'];
                                    $summary = $entry['summary'] ?? [];
                                @endphp
                                <tr>
                                    <td data-label="Module">
                                        <span class="eyebrow">{{ $module->key }}</span>
                                        <strong>{{ $module->name }}</strong>
                                        <p>{{ $module->description ?: 'No description supplied.' }}</p>
                                    </td>
                                    <td data-label="Status">
                                        <span class="pill">{{ ucfirst((string) $module->status) }}</span>
                                    </td>
                                    <td data-label="Snapshot">
                                        <span class="pill {{ $entry['has_snapshot'] ? 'success' : 'warning' }}">
                                            {{ $entry['has_snapshot'] ? 'Present' : 'Missing' }}
                                        </span>
                                    </td>
                                    <td data-label="Residue Summary">
                                        <div class="pill-list">
                                            <span class="pill">{{ $summary['tables'] ?? 0 }} tables</span>
                                            <span class="pill">{{ $summary['table_rows'] ?? 0 }} rows</span>
                                            <span class="pill">{{ $summary['settings'] ?? 0 }} settings</span>
                                            <span class="pill">{{ $summary['permissions'] ?? 0 }} permissions</span>
                                            <span class="pill">{{ $summary['storage_paths'] ?? 0 }} storage paths</span>
                                            <span class="pill">{{ $summary['packages'] ?? 0 }} packages</span>
                                        </div>

                                        <details class="settings-section-card">
                                            <summary>Residue Details</summary>
                                            <div class="data-table-wrap is-key-value-table">
                                                <table class="data-table is-key-value-table is-not-sticky-header">
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">Tables</th>
                                                            <td>
                                                                @forelse ($entry['tables'] as $table)
                                                                    <code>{{ $table['name'] }}</code> ({{ $table['exists'] ? 'exists' : 'missing' }}, {{ $table['rows'] ?? 0 }} rows)<br>
                                                                @empty
                                                                    None declared.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Settings</th>
                                                            <td>
                                                                @forelse ($entry['settings'] as $setting)
                                                                    <code>{{ $setting['key'] }}</code> ({{ $setting['rows'] }} rows)<br>
                                                                @empty
                                                                    None declared.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Permissions</th>
                                                            <td>
                                                                @forelse ($entry['permissions'] as $permission)
                                                                    <code>{{ $permission['key'] }}</code> ({{ $permission['rows'] }} rows)<br>
                                                                @empty
                                                                    None declared.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Navigation</th>
                                                            <td>
                                                                @forelse ($entry['navigation'] as $navigation)
                                                                    <code>{{ $navigation['source'] }}</code> ({{ $navigation['rows'] }} rows)<br>
                                                                @empty
                                                                    None found.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Storage</th>
                                                            <td>
                                                                @forelse ($entry['storage'] as $storage)
                                                                    <code>{{ $storage['path'] }}</code> ({{ $storage['exists'] ? 'exists' : 'missing' }}, {{ number_format(($storage['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                                                @empty
                                                                    None declared.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Packages</th>
                                                            <td>
                                                                @forelse ($entry['packages'] as $package)
                                                                    <code>{{ $package['storage_path'] ?: 'package row' }}</code> ({{ $package['rows'] }} rows, {{ number_format(($package['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                                                @empty
                                                                    None found.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Module Files</th>
                                                            <td>
                                                                @forelse ($entry['module_files'] as $files)
                                                                    <code>{{ $files['path'] }}</code> ({{ $files['exists'] ? 'exists' : 'missing' }}, {{ number_format(($files['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                                                @empty
                                                                    None found.
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>

                                        @if ($entry['protected'])
                                            <p class="help-text">{{ $entry['protected_reason'] }}</p>
                                        @elseif (! $entry['has_snapshot'])
                                            <p class="help-text">No v1 ownership snapshot is available for this module.</p>
                                        @endif
                                    </td>
                                    <td data-label="Protection">
                                        <span class="pill {{ $entry['protected'] ? 'warning' : 'success' }}">
                                            {{ $entry['protected'] ? 'Protected' : 'Standard' }}
                                        </span>
                                    </td>
                                    <td data-label="Actions">
                                        <form method="POST" action="{{ route('admin.module-cleaner.modules.plan', $module) }}">
                                            @csrf
                                            <div class="form-actions">
                                                <x-button type="submit" variant="secondary" size="compact" icon="clipboard-list" :disabled="! $entry['has_snapshot'] || $entry['protected']">
                                                    Dry Run Plan
                                                </x-button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app-shell>
