<?php

namespace Modules\ModuleCleaner\Settings;

use App\Support\SettingsSchema\SettingDefinition;

class ModuleCleanerSettingsCatalog
{
    public const KEYS = [
        'module_cleaner.require_backup',
        'module_cleaner.require_typed_confirmation',
        'module_cleaner.allow_dry_run',
        'module_cleaner.block_dependents',
        'module_cleaner.enable_orphan_detection',
        'module_cleaner.backup_retention_days',
        'module_cleaner.log_retention_days',
        'module_cleaner.quarantine_retention_days',
        'module_cleaner.email_on_cleanup',
        'module_cleaner.slack_webhook_url',
    ];

    /**
     * @return array<int, SettingDefinition>
     */
    public function definitions(): array
    {
        return [
            new SettingDefinition('module_cleaner.require_backup', 'module_cleaner_safety', 'module_cleaner', 'module_cleaner::messages.settings.definitions.require_backup.label', 'module_cleaner::messages.settings.definitions.require_backup.help', 'boolean', true),
            new SettingDefinition('module_cleaner.require_typed_confirmation', 'module_cleaner_safety', 'module_cleaner', 'module_cleaner::messages.settings.definitions.require_typed_confirmation.label', 'module_cleaner::messages.settings.definitions.require_typed_confirmation.help', 'boolean', true),
            new SettingDefinition('module_cleaner.allow_dry_run', 'module_cleaner_safety', 'module_cleaner', 'module_cleaner::messages.settings.definitions.allow_dry_run.label', 'module_cleaner::messages.settings.definitions.allow_dry_run.help', 'boolean', true),
            new SettingDefinition('module_cleaner.block_dependents', 'module_cleaner_safety', 'module_cleaner', 'module_cleaner::messages.settings.definitions.block_dependents.label', 'module_cleaner::messages.settings.definitions.block_dependents.help', 'boolean', true),
            new SettingDefinition('module_cleaner.enable_orphan_detection', 'module_cleaner_discovery', 'module_cleaner', 'module_cleaner::messages.settings.definitions.enable_orphan_detection.label', 'module_cleaner::messages.settings.definitions.enable_orphan_detection.help', 'boolean', false),
            new SettingDefinition('module_cleaner.backup_retention_days', 'module_cleaner_retention', 'module_cleaner', 'module_cleaner::messages.settings.definitions.backup_retention_days.label', 'module_cleaner::messages.settings.definitions.backup_retention_days.help', 'text', '30', validation: ['required', 'integer', 'min:0', 'max:3650']),
            new SettingDefinition('module_cleaner.log_retention_days', 'module_cleaner_retention', 'module_cleaner', 'module_cleaner::messages.settings.definitions.log_retention_days.label', 'module_cleaner::messages.settings.definitions.log_retention_days.help', 'text', '90', validation: ['required', 'integer', 'min:0', 'max:3650']),
            new SettingDefinition('module_cleaner.quarantine_retention_days', 'module_cleaner_retention', 'module_cleaner', 'module_cleaner::messages.settings.definitions.quarantine_retention_days.label', 'module_cleaner::messages.settings.definitions.quarantine_retention_days.help', 'text', '14', validation: ['required', 'integer', 'min:0', 'max:3650']),
            new SettingDefinition('module_cleaner.email_on_cleanup', 'module_cleaner_notifications', 'module_cleaner', 'module_cleaner::messages.settings.definitions.email_on_cleanup.label', 'module_cleaner::messages.settings.definitions.email_on_cleanup.help', 'boolean', false),
            new SettingDefinition('module_cleaner.slack_webhook_url', 'module_cleaner_notifications', 'module_cleaner', 'module_cleaner::messages.settings.definitions.slack_webhook_url.label', 'module_cleaner::messages.settings.definitions.slack_webhook_url.help', 'url', '', validation: ['nullable', 'url', 'max:255']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->definitions() as $definition) {
            $defaults[$definition->key] = $definition->default;
        }

        return $defaults;
    }
}
