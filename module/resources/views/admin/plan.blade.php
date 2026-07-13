<x-layouts.app-shell :title="__('module_cleaner::messages.plan.title')" :subtitle="__('module_cleaner::messages.plan.subtitle')">
        <x-slot:secondarySidebar>

            @include('module_cleaner::admin.partials.section-nav')
        </x-slot:secondarySidebar>

    <x-settings-category-layout :title="__('module_cleaner::messages.plan.title')" :description="__('module_cleaner::messages.plan.subtitle')">

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @php
            $module = $entry['module'];
            $quarantine = session('module_cleaner_quarantine');
        @endphp

        <x-card :title="$module->name" :description="__('module_cleaner::messages.plan.guard')">
            @if (! $plan)
                <x-empty-state :description="__('module_cleaner::messages.plan.empty')" />
                <div class="form-actions">
                    <form method="POST" action="{{ route('admin.module-cleaner.modules.plan', $module) }}">
                        @csrf
                        <x-button type="submit" icon="clipboard" :disabled="! $entry['has_snapshot'] || $entry['protected']">
                            {{ __('module_cleaner::messages.registry.dry_run') }}
                        </x-button>
                    </form>
                    <x-button :href="route('admin.module-cleaner.modules.show', $module)" variant="secondary" icon="eye">
                        {{ __('module_cleaner::messages.registry.details') }}
                    </x-button>
                </div>
            @else
                <div class="pill-list">
                    <span class="pill info">{{ $plan['module_key'] ?? $module->key }}</span>
                    <span class="pill success">{{ ($plan['dry_run'] ?? false) ? 'Dry run' : 'Prepared' }}</span>
                    @foreach (($plan['surfaces'] ?? []) as $surface)
                        <span class="pill">{{ $surface }}</span>
                    @endforeach
                </div>

                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.plan.surface') }}</th>
                                <th>{{ __('module_cleaner::messages.plan.action') }}</th>
                                <th>{{ __('module_cleaner::messages.plan.target') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($plan['items'] ?? []) as $item)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.plan.surface') }}">{{ $item['surface'] ?? 'surface' }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.plan.action') }}">{{ $item['action'] ?? 'planned' }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.plan.target') }}">
                                        @if (is_array($item['target'] ?? null))
                                            {{ implode(', ', $item['target']) }}
                                        @else
                                            {{ $item['target'] ?? '' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">{{ __('module_cleaner::messages.common.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="form-grid four">
                    <div class="settings-control-card">
                        <h3>{{ __('module_cleaner::messages.plan.backup_title') }}</h3>
                        <p class="form-help">{{ __('module_cleaner::messages.plan.backup_body') }}</p>
                        @if ($backup)
                            <div class="pill-list">
                                <span class="pill success">{{ __('module_cleaner::messages.plan.backup_confirmed') }}</span>
                                <span class="pill info">{{ $backup['relative_path'] ?? '' }}</span>
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.module-cleaner.modules.backup', $module) }}">
                                @csrf
                                <x-button type="submit" icon="database-backup">
                                    {{ __('module_cleaner::messages.plan.create_backup') }}
                                </x-button>
                            </form>
                        @endif
                    </div>

                    <div class="settings-control-card">
                        <h3>{{ __('module_cleaner::messages.plan.quarantine_title') }}</h3>
                        <p class="form-help">{{ __('module_cleaner::messages.plan.quarantine_body') }}</p>
                        @if ($quarantine)
                            <div class="pill-list">
                                <span class="pill success">{{ __('module_cleaner::messages.plan.quarantine_confirmed') }}</span>
                                <span class="pill info">{{ $quarantine['relative_path'] ?? '' }}</span>
                            </div>
                        @elseif ($backup)
                            <form method="POST" action="{{ route('admin.module-cleaner.modules.quarantine', $module) }}">
                                @csrf
                                <x-button type="submit" icon="archive">
                                    {{ __('module_cleaner::messages.plan.create_quarantine') }}
                                </x-button>
                            </form>
                        @else
                            <x-empty-state :description="__('module_cleaner::messages.plan.backup_required')" />
                        @endif
                    </div>

                    <div class="settings-control-card">
                        <h3>{{ __('module_cleaner::messages.plan.execute_title') }}</h3>
                        <p class="form-help">{{ __('module_cleaner::messages.plan.execute_body') }}</p>
                        @if ($backup && $execution)
                            <form method="POST" action="{{ route('admin.settings.addon-modules.modules.purge', $module) }}">
                                @csrf
                                <input type="hidden" name="backup_confirmed" value="1">
                                @foreach (($execution['surfaces'] ?? []) as $surface)
                                    <input type="hidden" name="surfaces[]" value="{{ $surface }}">
                                @endforeach
                                <label class="field">
                                    <span>{{ __('module_cleaner::messages.plan.confirm_module_key') }}</span>
                                    <input type="text" name="confirm_module_key" value="" placeholder="{{ $execution['confirm_module_key'] ?? $module->key }}" required>
                                </label>
                                <x-button type="submit" variant="danger" icon="trash">
                                    {{ __('module_cleaner::messages.plan.execute_host_purge') }}
                                </x-button>
                            </form>
                        @else
                            <x-empty-state :description="__('module_cleaner::messages.plan.backup_required')" />
                        @endif
                    </div>
                </div>
            @endif
        </x-card>
    </x-settings-category-layout>
</x-layouts.app-shell>
