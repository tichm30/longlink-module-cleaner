<x-layouts.app-shell :title="__('module_cleaner::messages.orphans.title')" :subtitle="__('module_cleaner::messages.orphans.subtitle')">
    <div class="settings-layout">
        @include('module_cleaner::admin.partials.section-nav')

        <x-card :title="__('module_cleaner::messages.orphans.title')" :description="__('module_cleaner::messages.orphans.subtitle')">
            @unless ($detectionEnabled)
                <div class="alert alert-warning">{{ __('module_cleaner::messages.orphans.disabled') }}</div>
            @endunless

            @if ($candidates === [])
                <x-empty-state :description="__('module_cleaner::messages.orphans.empty')" />
            @else
                <div class="data-table-wrap">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.common.name') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.size') }}</th>
                                <th>{{ __('module_cleaner::messages.plan.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($candidates as $candidate)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.common.name') }}"><code>{{ $candidate['name'] ?? '' }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.size') }}">{{ $candidate['size'] ?? 'Unknown' }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.plan.action') }}"><span class="pill warning">Review only</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app-shell>
