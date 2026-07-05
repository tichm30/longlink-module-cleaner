<x-layouts.app-shell :title="__('module_cleaner::messages.backups.title')" :subtitle="__('module_cleaner::messages.backups.subtitle')">
        <x-slot:secondarySidebar>

            @include('module_cleaner::admin.partials.section-nav')
        </x-slot:secondarySidebar>

    <x-settings-category-layout :title="__('module_cleaner::messages.backups.title')" :description="__('module_cleaner::messages.backups.subtitle')">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <x-card :title="__('module_cleaner::messages.backups.records_title')">
            @if ($backupRows === [])
                <x-empty-state :description="__('module_cleaner::messages.backups.no_records')" />
            @else
                <div class="data-table-wrap is-card-table">
                    <table class="data-table is-mobile-card-table">
                        <thead>
                            <tr>
                                <th>{{ __('module_cleaner::messages.logs.module') }}</th>
                                <th>{{ __('module_cleaner::messages.backups.backup_ref') }}</th>
                                <th>{{ __('module_cleaner::messages.logs.status') }}</th>
                                <th>{{ __('module_cleaner::messages.backups.restore') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backupRows as $row)
                                <tr>
                                    <td data-label="{{ __('module_cleaner::messages.logs.module') }}"><code>{{ $row['module_key'] ?? '' }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.backups.backup_ref') }}"><code>{{ $row['backup_ref'] ?? '' }}</code></td>
                                    <td data-label="{{ __('module_cleaner::messages.logs.status') }}"><span class="pill info">{{ $row['status'] ?? 'created' }}</span></td>
                                    <td data-label="{{ __('module_cleaner::messages.backups.restore') }}">
                                        <form method="POST" action="{{ route('admin.module-cleaner.backups.restore-plan', $row['id']) }}">
                                            @csrf
                                            <x-button type="submit" icon="rotate-ccw" variant="secondary">{{ __('module_cleaner::messages.backups.prepare_restore') }}</x-button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card :title="__('module_cleaner::messages.backups.title')" :description="__('module_cleaner::messages.backups.path', ['path' => $storagePath])">
            @if ($backups === [])
                <x-empty-state :description="__('module_cleaner::messages.backups.empty')" />
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
    </x-settings-category-layout>
</x-layouts.app-shell>
