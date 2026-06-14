<x-layouts.app-shell :title="__('module_cleaner::messages.details.title')" :subtitle="__('module_cleaner::messages.details.subtitle')">
    <div class="settings-layout">
        @include('module_cleaner::admin.partials.section-nav')

        @php
            $module = $entry['module'];
            $summary = $entry['summary'] ?? [];
        @endphp

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <x-card :title="$module->name" :description="$module->description ?: $module->key">
            <div class="pill-list">
                <span class="pill">{{ $module->key }}</span>
                <span class="pill {{ $entry['has_snapshot'] ? 'success' : 'warning' }}">{{ $entry['has_snapshot'] ? __('module_cleaner::messages.registry.present') : __('module_cleaner::messages.registry.missing') }}</span>
                <span class="pill {{ $entry['protected'] ? 'warning' : 'success' }}">{{ $entry['protected'] ? __('module_cleaner::messages.registry.protected') : __('module_cleaner::messages.registry.standard') }}</span>
                <span class="pill">{{ $summary['tables'] ?? 0 }} tables</span>
                <span class="pill">{{ $summary['packages'] ?? 0 }} packages</span>
            </div>

            @if ($entry['protected_reason'])
                <div class="alert alert-warning">{{ $entry['protected_reason'] }}</div>
            @elseif (! $entry['has_snapshot'])
                <div class="alert alert-warning">No v1 ownership snapshot is available for this module.</div>
            @endif

            <div class="form-actions">
                <form method="POST" action="{{ route('admin.module-cleaner.modules.plan', $module) }}">
                    @csrf
                    <x-button type="submit" icon="clipboard" :disabled="! $entry['has_snapshot'] || $entry['protected']">
                        {{ __('module_cleaner::messages.registry.dry_run') }}
                    </x-button>
                </form>
                <x-button :href="route('admin.module-cleaner.registry')" variant="secondary" icon="arrow-left">{{ __('module_cleaner::messages.sections.registry') }}</x-button>
            </div>
        </x-card>

        <x-card :title="__('module_cleaner::messages.details.tables')">
            <div class="data-table-wrap">
                <table class="data-table is-mobile-card-table">
                    <thead><tr><th>Name</th><th>Exists</th><th>Rows</th></tr></thead>
                    <tbody>
                        @forelse ($entry['tables'] as $table)
                            <tr><td data-label="Name"><code>{{ $table['name'] }}</code></td><td data-label="Exists">{{ $table['exists'] ? 'Yes' : 'No' }}</td><td data-label="Rows">{{ $table['rows'] ?? 0 }}</td></tr>
                        @empty
                            <tr><td colspan="3">{{ __('module_cleaner::messages.details.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="form-grid two">
            <x-card :title="__('module_cleaner::messages.details.settings')">
                <div class="pill-list">
                    @forelse ($entry['settings'] as $setting)
                        <span class="pill"><code>{{ $setting['key'] }}</code> {{ $setting['rows'] }} rows</span>
                    @empty
                        <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                    @endforelse
                </div>
            </x-card>

            <x-card :title="__('module_cleaner::messages.details.permissions')">
                <div class="pill-list">
                    @forelse ($entry['permissions'] as $permission)
                        <span class="pill"><code>{{ $permission['key'] }}</code> {{ $permission['rows'] }} rows</span>
                    @empty
                        <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                    @endforelse
                </div>
            </x-card>
        </div>

        <x-card :title="__('module_cleaner::messages.details.navigation')">
            <div class="pill-list">
                @forelse ($entry['navigation'] as $navigation)
                    <span class="pill"><code>{{ $navigation['source'] }}</code> {{ $navigation['rows'] }} rows</span>
                @empty
                    <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                @endforelse
            </div>
        </x-card>

        <div class="form-grid two">
            <x-card :title="__('module_cleaner::messages.details.storage')">
                <div class="pill-list">
                    @forelse ($entry['storage'] as $storage)
                        <span class="pill"><code>{{ $storage['path'] }}</code> {{ $storage['exists'] ? 'exists' : 'missing' }} - {{ number_format(($storage['bytes'] ?? 0) / 1024, 1) }} KB</span>
                    @empty
                        <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                    @endforelse
                </div>
            </x-card>

            <x-card :title="__('module_cleaner::messages.details.packages')">
                <div class="pill-list">
                    @forelse ($entry['packages'] as $package)
                        <span class="pill"><code>{{ $package['storage_path'] ?: 'package row' }}</code> {{ $package['rows'] }} rows - {{ number_format(($package['bytes'] ?? 0) / 1024, 1) }} KB</span>
                    @empty
                        <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                    @endforelse
                </div>
            </x-card>
        </div>

        <x-card :title="__('module_cleaner::messages.details.module_files')">
            <div class="pill-list">
                @forelse ($entry['module_files'] as $files)
                    <span class="pill"><code>{{ $files['path'] }}</code> {{ $files['exists'] ? 'exists' : 'missing' }} - {{ number_format(($files['bytes'] ?? 0) / 1024, 1) }} KB</span>
                @empty
                    <span class="pill">{{ __('module_cleaner::messages.details.none') }}</span>
                @endforelse
            </div>
        </x-card>
    </div>
</x-layouts.app-shell>
