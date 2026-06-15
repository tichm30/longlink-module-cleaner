<x-layouts.app-shell :title="__('module_cleaner::messages.settings.title')" :subtitle="__('module_cleaner::messages.settings.subtitle')">
    <div class="settings-category-layout">
        @include('module_cleaner::admin.partials.section-nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.module-cleaner.settings.update') }}">
            @csrf

            <div class="form-stack">
                <div class="form-grid two">
                    <x-card :title="__('module_cleaner::messages.settings.safety')">
                        <p class="form-help">{{ __('module_cleaner::messages.plan.guard') }}</p>
                        <p class="form-help">module_cleaner.purge is required for backup creation and host purge handoff.</p>
                        <div class="form-stack">
                            <label class="choice-row">
                                <input type="checkbox" name="require_backup" value="1" @checked($defaults['require_backup'])>
                                <span>{{ __('module_cleaner::messages.settings.require_backup') }}</span>
                            </label>
                            <label class="choice-row">
                                <input type="checkbox" name="require_typed_confirmation" value="1" @checked($defaults['require_typed_confirmation'])>
                                <span>{{ __('module_cleaner::messages.settings.typed_confirmation') }}</span>
                            </label>
                            <label class="choice-row">
                                <input type="checkbox" name="allow_dry_run" value="1" @checked($defaults['allow_dry_run'])>
                                <span>{{ __('module_cleaner::messages.settings.allow_dry_run') }}</span>
                            </label>
                            <label class="choice-row">
                                <input type="checkbox" name="block_dependents" value="1" @checked($defaults['block_dependents'])>
                                <span>{{ __('module_cleaner::messages.settings.block_dependents') }}</span>
                            </label>
                            <label class="choice-row">
                                <input type="checkbox" name="enable_orphan_detection" value="1" @checked($defaults['enable_orphan_detection'])>
                                <span>{{ __('module_cleaner::messages.settings.orphan_detection') }}</span>
                            </label>
                        </div>
                    </x-card>

                    <x-card :title="__('module_cleaner::messages.settings.automation')">
                        <div class="form-grid single">
                            <label class="field">
                                <span>{{ __('module_cleaner::messages.settings.backup_retention') }}</span>
                                <input type="number" name="backup_retention_days" value="{{ old('backup_retention_days', $defaults['backup_retention_days']) }}" min="0" max="3650">
                                @error('backup_retention_days')<span class="form-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="field">
                                <span>{{ __('module_cleaner::messages.settings.log_retention') }}</span>
                                <input type="number" name="log_retention_days" value="{{ old('log_retention_days', $defaults['log_retention_days']) }}" min="0" max="3650">
                                @error('log_retention_days')<span class="form-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="field">
                                <span>{{ __('module_cleaner::messages.settings.quarantine_retention') }}</span>
                                <input type="number" name="quarantine_retention_days" value="{{ old('quarantine_retention_days', $defaults['quarantine_retention_days']) }}" min="0" max="3650">
                                @error('quarantine_retention_days')<span class="form-error">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </x-card>
                </div>

                <x-card :title="__('module_cleaner::messages.settings.notifications')">
                    <div class="form-grid two">
                        <label class="choice-row">
                            <input type="checkbox" name="email_on_cleanup" value="1" @checked($defaults['email_on_cleanup'])>
                            <span>{{ __('module_cleaner::messages.settings.email_on_cleanup') }}</span>
                        </label>
                        <label class="field">
                            <span>{{ __('module_cleaner::messages.settings.slack_webhook') }}</span>
                            <input type="url" name="slack_webhook_url" value="{{ old('slack_webhook_url', $defaults['slack_webhook_url']) }}" placeholder="https://hooks.slack.com/services/...">
                            @error('slack_webhook_url')<span class="form-error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </x-card>

                <div class="form-actions">
                    <x-button type="submit" icon="save">{{ __('module_cleaner::messages.settings.save') }}</x-button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app-shell>
