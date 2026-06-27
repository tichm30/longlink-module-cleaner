<x-layouts.app-shell :title="__('module_cleaner::messages.logs.title')" :subtitle="__('module_cleaner::messages.logs.subtitle')">
        <x-slot:secondarySidebar>

            @include('module_cleaner::admin.partials.section-nav')
        </x-slot:secondarySidebar>

    <x-settings-category-layout :title="__('module_cleaner::messages.logs.title')" :description="__('module_cleaner::messages.logs.subtitle')">

        <x-card :title="__('module_cleaner::messages.logs.title')">
            @if ($logs === [])
                <x-empty-state :description="__('module_cleaner::messages.logs.empty')" />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.logs.module') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.action') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.status') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.size') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.logs.module') }}"><code>{{ $log['module_key'] }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.action') }}">{{ $log['action'] }}</td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.status') }}"><span class="pill info">{{ $log['status'] }}</span></td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.size') }}">{{ number_format($log['size_freed_bytes'] / 1024, 1) }} KB</td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.created') }}">{{ $log['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </x-settings-category-layout>
</x-layouts.app-shell>
