<x-responsive-category-links
    :items="$sections"
    :active-key="$activeSection"
    :menu-label="__('module_cleaner::messages.sections.title')"
    :mobile-menu-label="__('app.settings.mobile_category_menu')"
    :menu-help="__('module_cleaner::messages.sections.description')"
/>
