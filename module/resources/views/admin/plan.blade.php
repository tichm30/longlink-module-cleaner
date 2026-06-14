<x-layouts.app-shell :title="__('module_cleaner::messages.plan.title')" :subtitle="__('module_cleaner::messages.plan.subtitle')">
    <div class="settings-category-layout">
        @include('module_cleaner::admin.partials.section-nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @php($module = $entry['module'])

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

                <div class="data-table-wrap">
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
            @endif
        </x-card>
    </div>
</x-layouts.app-shell>
