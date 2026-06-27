<x-layouts.app-shell :title="__('module_cleaner::messages.registry.title')" :subtitle="__('module_cleaner::messages.registry.subtitle')">
    <x-settings-category-layout :title="__('module_cleaner::messages.registry.title')" :description="__('module_cleaner::messages.registry.subtitle')">

        <x-slot:sidebar>

            @include('module_cleaner::admin.partials.section-nav')

        </x-slot:sidebar>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <x-card :title="__('module_cleaner::messages.registry.title')">
            @if ($modules === [])
                <x-empty-state :description="__('module_cleaner::messages.registry.empty')" />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.registry.module') }}</th>
                                <th>{{ __('module_cleaner::messages.registry.status') }}</th>
                                <th>{{ __('module_cleaner::messages.registry.residue') }}</th>
                                <th>{{ __('module_cleaner::messages.registry.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $entry)
                                @php
                                    $module = $entry['module'];
                                    $summary = $entry['summary'] ?? [];
                                @endphp
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.registry.module') }}">
                                        <span class="eyebrow">{{ $module->key }}</span>
                                        <strong>{{ $module->name }}</strong>
                                        <p>{{ $module->description ?: 'No description supplied.' }}</p>
                                    </td>
                                    <td data-label="{{ __('module_cleaner::messages.registry.status') }}">
                                        <div class="pill-list">
                                            <span class="pill">{{ ucfirst((string) $module->status) }}</span>
                                            <span class="pill {{ $entry['has_snapshot'] ? 'success' : 'warning' }}">
                                                {{ $entry['has_snapshot'] ? __('module_cleaner::messages.registry.present') : __('module_cleaner::messages.registry.missing') }}
                                            </span>
                                            <span class="pill {{ $entry['protected'] ? 'warning' : 'success' }}">
                                                {{ $entry['protected'] ? __('module_cleaner::messages.registry.protected') : __('module_cleaner::messages.registry.ready') }}
                                            </span>
                                        </div>
                                        @if ($entry['protected_reason'])
                                            <p class="form-help">{{ $entry['protected_reason'] }}</p>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('module_cleaner::messages.registry.residue') }}">
                                        <details>
                                            <summary>{{ __('module_cleaner::messages.registry.surface_breakdown') }}</summary>
                                            <div class="pill-list">
                                                <span class="pill">{{ $summary['tables'] ?? 0 }} tables</span>
                                                <span class="pill">{{ $summary['settings'] ?? 0 }} settings</span>
                                                <span class="pill">{{ $summary['permissions'] ?? 0 }} permissions</span>
                                                <span class="pill">{{ $summary['storage_paths'] ?? 0 }} storage</span>
                                                <span class="pill">{{ $summary['packages'] ?? 0 }} packages</span>
                                            </div>
                                        </details>
                                    </td>
                                    <td data-label="{{ __('module_cleaner::messages.registry.actions') }}">
                                        <div class="form-actions">
                                            <x-button :href="route('admin.module-cleaner.modules.show', $module)" variant="secondary" icon="eye">
                                                {{ __('module_cleaner::messages.registry.details') }}
                                            </x-button>
                                            <form method="POST" action="{{ route('admin.module-cleaner.modules.plan', $module) }}">
                                                @csrf
                                                <x-button type="submit" icon="clipboard" :disabled="! $entry['has_snapshot'] || $entry['protected']">
                                                    {{ __('module_cleaner::messages.registry.dry_run') }}
                                                </x-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </x-settings-category-layout>
</x-layouts.app-shell>
