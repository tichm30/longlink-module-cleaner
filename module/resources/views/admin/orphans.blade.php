<x-layouts.app-shell :title="__('module_cleaner::messages.orphans.title')" :subtitle="__('module_cleaner::messages.orphans.subtitle')">
    <x-settings-category-layout :title="__('module_cleaner::messages.orphans.title')" :description="__('module_cleaner::messages.orphans.subtitle')">

        <x-slot:categories>

            @include('module_cleaner::admin.partials.section-nav')

        </x-slot:categories>

        <x-card :title="__('module_cleaner::messages.orphans.title')" :description="__('module_cleaner::messages.orphans.subtitle')">
            @unless ($detectionEnabled)
                <div class="alert alert-warning">{{ __('module_cleaner::messages.orphans.disabled') }}</div>
            @endunless

            @if ($candidates === [])
                <x-empty-state :description="__('module_cleaner::messages.orphans.empty')" />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.common.name') }}</th>
                                <th>{{ __('module_cleaner::messages.orphans.rows') }}</th>
                                <th>{{ __('module_cleaner::messages.orphans.confidence') }}</th>
                                <th>{{ __('module_cleaner::messages.orphans.evidence') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($candidates as $candidate)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.common.name') }}"><code>{{ $candidate['name'] ?? '' }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.orphans.rows') }}">{{ $candidate['rows'] ?? 'Unknown' }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.orphans.confidence') }}"><span class="pill warning">{{ $candidate['confidence'] ?? 'review' }}</span></td>
                                    <td data-label="{{ __('module_cleaner::messages.orphans.evidence') }}">{{ implode(', ', $candidate['evidence'] ?? []) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </x-settings-category-layout>
</x-layouts.app-shell>
