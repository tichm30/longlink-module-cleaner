<x-app-layout>
    <x-slot name="title">{{ __('module_cleaner::messages.navigation.cleaner') }}</x-slot>

    <div class="page-heading">
        <div>
            <p class="eyebrow">Addon Modules</p>
            <h1>{{ __('module_cleaner::messages.navigation.cleaner') }}</h1>
            <p>Review install-time ownership snapshots, prepare dry-run cleanup plans, and keep purge execution inside the host safety boundary.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($latestPlan)
        <x-card class="settings-section">
            <x-slot name="title">Latest Dry-Run Plan</x-slot>
            <dl class="responsive-definition-list">
                <div>
                    <dt>Module</dt>
                    <dd>{{ $latestPlan['module_key'] ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt>Mode</dt>
                    <dd>{{ ($latestPlan['dry_run'] ?? false) ? 'Dry run' : 'Execute' }}</dd>
                </div>
                <div>
                    <dt>Surfaces</dt>
                    <dd>{{ implode(', ', $latestPlan['surfaces'] ?? []) }}</dd>
                </div>
            </dl>
            <div class="module-cleaner-plan">
                @foreach (($latestPlan['items'] ?? []) as $item)
                    <div class="module-cleaner-plan__item">
                        <strong>{{ $item['surface'] ?? 'surface' }}</strong>
                        <span>{{ $item['action'] ?? 'planned' }}</span>
                        <code>{{ is_array($item['target'] ?? null) ? implode(', ', $item['target']) : ($item['target'] ?? '') }}</code>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <x-card class="settings-section">
        <x-slot name="title">Snapshot Inventory</x-slot>
        <x-slot name="description">Modules without a v1 ownership snapshot are visible, but cannot be purged by this cleaner. The module_cleaner.purge permission is reserved for the future host-guarded purge screen and is not exposed in this release.</x-slot>

        <div class="responsive-data-grid">
            @forelse ($modules as $entry)
                @php
                    $module = $entry['module'];
                    $summary = $entry['summary'] ?? [];
                @endphp
                <article class="responsive-data-grid__row">
                    <div>
                        <span class="eyebrow">{{ $module->key }}</span>
                        <h3>{{ $module->name }}</h3>
                        <p>{{ $module->description ?: 'No description supplied.' }}</p>
                    </div>
                    <dl>
                        <div>
                            <dt>Status</dt>
                            <dd>{{ ucfirst((string) $module->status) }}</dd>
                        </div>
                        <div>
                            <dt>Snapshot</dt>
                            <dd>{{ $entry['has_snapshot'] ? 'Present' : 'Missing' }}</dd>
                        </div>
                        <div>
                            <dt>Tables</dt>
                            <dd>{{ $summary['tables'] ?? 0 }} tables, {{ $summary['table_rows'] ?? 0 }} rows</dd>
                        </div>
                        <div>
                            <dt>Settings</dt>
                            <dd>{{ $summary['settings'] ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt>Permissions</dt>
                            <dd>{{ $summary['permissions'] ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt>Storage</dt>
                            <dd>{{ $summary['storage_paths'] ?? 0 }} paths</dd>
                        </div>
                        <div>
                            <dt>Packages</dt>
                            <dd>{{ $summary['packages'] ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt>Protection</dt>
                            <dd>{{ $entry['protected'] ? 'Protected' : 'Standard' }}</dd>
                        </div>
                    </dl>
                    <details class="module-cleaner-inventory">
                        <summary>Residue Details</summary>

                        <div class="responsive-definition-list">
                            <div>
                                <dt>Tables</dt>
                                <dd>
                                    @forelse ($entry['tables'] as $table)
                                        <code>{{ $table['name'] }}</code> ({{ $table['exists'] ? 'exists' : 'missing' }}, {{ $table['rows'] ?? 0 }} rows)<br>
                                    @empty
                                        None declared.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Settings</dt>
                                <dd>
                                    @forelse ($entry['settings'] as $setting)
                                        <code>{{ $setting['key'] }}</code> ({{ $setting['rows'] }} rows)<br>
                                    @empty
                                        None declared.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Permissions</dt>
                                <dd>
                                    @forelse ($entry['permissions'] as $permission)
                                        <code>{{ $permission['key'] }}</code> ({{ $permission['rows'] }} rows)<br>
                                    @empty
                                        None declared.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Navigation</dt>
                                <dd>
                                    @forelse ($entry['navigation'] as $navigation)
                                        <code>{{ $navigation['source'] }}</code> ({{ $navigation['rows'] }} rows)<br>
                                    @empty
                                        None found.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Storage</dt>
                                <dd>
                                    @forelse ($entry['storage'] as $storage)
                                        <code>{{ $storage['path'] }}</code> ({{ $storage['exists'] ? 'exists' : 'missing' }}, {{ number_format(($storage['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                    @empty
                                        None declared.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Packages</dt>
                                <dd>
                                    @forelse ($entry['packages'] as $package)
                                        <code>{{ $package['storage_path'] ?: 'package row' }}</code> ({{ $package['rows'] }} rows, {{ number_format(($package['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                    @empty
                                        None found.
                                    @endforelse
                                </dd>
                            </div>
                            <div>
                                <dt>Module Files</dt>
                                <dd>
                                    @forelse ($entry['module_files'] as $files)
                                        <code>{{ $files['path'] }}</code> ({{ $files['exists'] ? 'exists' : 'missing' }}, {{ number_format(($files['bytes'] ?? 0) / 1024, 1) }} KB)<br>
                                    @empty
                                        None found.
                                    @endforelse
                                </dd>
                            </div>
                        </div>
                    </details>
                    @if ($entry['protected'])
                        <p class="help-text">{{ $entry['protected_reason'] }}</p>
                    @elseif (! $entry['has_snapshot'])
                        <p class="help-text">No v1 ownership snapshot is available for this module.</p>
                    @endif
                    <div class="form-actions">
                        <form method="POST" action="{{ route('admin.module-cleaner.modules.plan', $module) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" :disabled="! $entry['has_snapshot'] || $entry['protected']">
                                Dry Run Plan
                            </x-button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <p>No addon modules have been registered yet.</p>
                </div>
            @endforelse
        </div>
    </x-card>
</x-app-layout>
