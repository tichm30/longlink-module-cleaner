<x-layouts.app-shell :title="__('module_cleaner::messages.quarantine.title')" :subtitle="__('module_cleaner::messages.quarantine.subtitle')">
    <x-settings-category-layout :title="__('module_cleaner::messages.quarantine.title')" :description="__('module_cleaner::messages.quarantine.subtitle')">

        <x-slot:sidebar>

            @include('module_cleaner::admin.partials.section-nav')

        </x-slot:sidebar>

        <x-card :title="__('module_cleaner::messages.quarantine.title')" :description="__('module_cleaner::messages.quarantine.path', ['path' => $storagePath])">
            @if ($items === [])
                <x-empty-state :description="__('module_cleaner::messages.quarantine.empty')" />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.common.name') }}</th>
                                <th>{{ __('module_cleaner::messages.common.size') }}</th>
                                <th>{{ __('module_cleaner::messages.common.modified') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.common.name') }}"><code>{{ $item['name'] }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.common.size') }}">{{ number_format($item['size'] / 1024, 1) }} KB</td>
                                    <td data-label="{{ __('module_cleaner::messages.common.modified') }}">{{ $item['modified_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </x-settings-category-layout>
</x-layouts.app-shell>
