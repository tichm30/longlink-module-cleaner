<x-layouts.app-shell :title="__('module_cleaner::messages.settings.title')" :subtitle="__('module_cleaner::messages.settings.subtitle')">
        <x-slot:secondarySidebar>

            @include('module_cleaner::admin.partials.section-nav')
        </x-slot:secondarySidebar>

    <x-settings-category-layout :title="__('module_cleaner::messages.settings.title')" :description="__('module_cleaner::messages.settings.subtitle')">

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.module-cleaner.settings.update') }}">
            @csrf

            <div class="stack">
                <div class="form-grid four">
                    <x-card :title="__('module_cleaner::messages.settings.safety')">
                        <p class="form-help">{{ __('module_cleaner::messages.plan.guard') }}</p>
                        <p class="form-help">module_cleaner.purge is required for backup creation and host purge handoff.</p>
                        <div class="stack">
                            <x-form.checkbox name="require_backup" :label="__('module_cleaner::messages.settings.require_backup')" :checked="$defaults['require_backup']" />
                            <x-form.checkbox name="require_typed_confirmation" :label="__('module_cleaner::messages.settings.typed_confirmation')" :checked="$defaults['require_typed_confirmation']" />
                            <x-form.checkbox name="allow_dry_run" :label="__('module_cleaner::messages.settings.allow_dry_run')" :checked="$defaults['allow_dry_run']" />
                            <x-form.checkbox name="block_dependents" :label="__('module_cleaner::messages.settings.block_dependents')" :checked="$defaults['block_dependents']" />
                            <x-form.checkbox name="enable_orphan_detection" :label="__('module_cleaner::messages.settings.orphan_detection')" :checked="$defaults['enable_orphan_detection']" />
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
                    <div class="form-grid four">
                        <x-form.checkbox name="email_on_cleanup" :label="__('module_cleaner::messages.settings.email_on_cleanup')" :checked="$defaults['email_on_cleanup']" />
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
    </x-settings-category-layout>
</x-layouts.app-shell>
