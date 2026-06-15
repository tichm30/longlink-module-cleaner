<x-layouts.app-shell :title="__('module_cleaner::messages.page.title')" :subtitle="__('module_cleaner::messages.page.subtitle')">
    <div class="settings-category-layout">
        @include('module_cleaner::admin.partials.section-nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="form-stack">
            <x-card :title="__('module_cleaner::messages.dashboard.summary')" :description="__('module_cleaner::messages.dashboard.summary_body')">
                <div class="form-actions">
                    <x-button :href="route('admin.module-cleaner.registry')" icon="blocks">{{ __('module_cleaner::messages.dashboard.open_registry') }}</x-button>
                    <x-button :href="route('admin.module-cleaner.settings')" variant="secondary" icon="settings">{{ __('module_cleaner::messages.dashboard.open_settings') }}</x-button>
                </div>
            </x-card>

            <div class="form-grid two">
                <x-card :title="__('module_cleaner::messages.dashboard.modules_title')" :description="__('module_cleaner::messages.dashboard.snapshot_help')">
                    <span class="eyebrow">{{ __('module_cleaner::messages.dashboard.snapshots') }}</span>
                    <strong>{{ $summary['snapshots'] }} / {{ $summary['modules'] }}</strong>
                </x-card>

                <x-card :title="__('module_cleaner::messages.dashboard.protected_title')" :description="__('module_cleaner::messages.dashboard.protected_help')">
                    <span class="eyebrow">{{ __('module_cleaner::messages.registry.protected') }}</span>
                    <strong>{{ $summary['protected'] }}</strong>
                </x-card>

                <x-card :title="__('module_cleaner::messages.dashboard.tables_title')" :description="__('module_cleaner::messages.dashboard.tables_help')">
                    <span class="eyebrow">{{ __('module_cleaner::messages.details.tables') }}</span>
                    <strong>{{ $summary['tables'] }}</strong>
                </x-card>

                <x-card :title="__('module_cleaner::messages.dashboard.packages_title')" :description="__('module_cleaner::messages.dashboard.packages_help')">
                    <span class="eyebrow">{{ __('module_cleaner::messages.details.packages') }}</span>
                    <strong>{{ $summary['packages'] }}</strong>
                </x-card>
            </div>
        </div>

        @if ($latestPlan)
            <x-card :title="__('module_cleaner::messages.dashboard.latest_plan')" :description="__('module_cleaner::messages.plan.guard')">
                <div class="pill-list">
                    <span class="pill info">{{ $latestPlan['module_key'] ?? 'module' }}</span>
                    <span class="pill success">{{ ($latestPlan['dry_run'] ?? false) ? 'Dry run' : 'Prepared' }}</span>
                    @foreach (($latestPlan['surfaces'] ?? []) as $surface)
                        <span class="pill">{{ $surface }}</span>
                    @endforeach
                </div>
            </x-card>
        @endif

        <x-card :title="__('module_cleaner::messages.dashboard.recent_logs')">
            @if ($logs === [])
                <x-empty-state :description="__('module_cleaner::messages.logs.empty')" />
            @else
                <div class="data-table-wrap">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.logs.module') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.action') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.status') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.logs.module') }}">{{ $log['module_key'] }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.action') }}">{{ $log['action'] }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.status') }}"><span class="pill info">{{ $log['status'] }}</span></td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.created') }}">{{ $log['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app-shell>
