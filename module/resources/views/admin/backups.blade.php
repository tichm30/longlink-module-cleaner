<x-layouts.app-shell :title="__('module_cleaner::messages.backups.title')" :subtitle="__('module_cleaner::messages.backups.subtitle')">
    <div class="settings-layout">
        @include('module_cleaner::admin.partials.section-nav')

        <x-card :title="__('module_cleaner::messages.backups.title')" :description="__('module_cleaner::messages.backups.path', ['path' => $storagePath])">
            @if ($backups === [])
                <x-empty-state :description="__('module_cleaner::messages.backups.empty')" />
            @else
                <div class="data-table-wrap">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.common.name') }}</th>
                                <th>{{ __('module_cleaner::messages.common.size') }}</th>
                                <th>{{ __('module_cleaner::messages.common.modified') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backups as $backup)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.common.name') }}"><code>{{ $backup['name'] }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.common.size') }}">{{ number_format($backup['size'] / 1024, 1) }} KB</td>
                                    <td data-label="{{ __('module_cleaner::messages.common.modified') }}">{{ $backup['modified_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app-shell>
