<x-card :title="__('module_cleaner::messages.sections.title')" :description="__('module_cleaner::messages.sections.description')">
    <div class="settings-nav-list">
        @foreach ($sections as $section)
            <x-button :href="$section['route']" :variant="$activeSection === $section['key'] ? 'primary' : 'secondary'" :icon="$section['icon']">
                {{ $section['label'] }}
            </x-button>
        @endforeach
    </div>
</x-card>
