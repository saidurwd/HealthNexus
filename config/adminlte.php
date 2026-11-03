<?php

use App\Support\ModuleMenuFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter;

/*
|--------------------------------------------------------------------------
| Laravel AdminLTE Configuration
|--------------------------------------------------------------------------
|
| The options below are grouped by topic, and every group is introduced by
| the comment block right above it. The order of the groups is: the basics
| (title, logos, urls), the assets, the layout and the styling, the
| authentication views, the menu, the plugins and finally the integrations.
|
| Note the options added for AdminLTE v4 live inside a nested array of their
| own (like 'color_mode' or 'lockscreen'), while the older ones keep their
| flat name (like 'sidebar_nav_pills'). Renaming the flat ones would break
| every configuration file already published into an application, so they
| are kept as they are.
|
| The full documentation is available here:
| https://jeroennoten.github.io/Laravel-AdminLTE/
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel. The prefix and
    | the postfix are placed around the title of every page, and a view can
    | override any of the three through the section of the same name.
    |
    | For detailed instructions you can look the title section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'title' => 'Health Nexus',
    'title_prefix' => '',
    'title_postfix' => '- Health Nexus',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon. Enable 'use_ico_only' to link the
    | 'public/favicons/favicon.ico' file alone, or 'use_full_favicon' to also
    | link the whole set of touch icons, png sizes and the web app manifest
    | that the same folder is expected to provide.
    |
    | For detailed instructions you can look the favicon section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel. The 'logo' option holds
    | the brand text (html is allowed) and 'logo_img' the image placed next to
    | it, relative to the public folder.
    |
    | The optional 'logo_img_xl' option enables the AdminLTE logo switch: the
    | 'logo_img' one is then only shown while the sidebar is collapsed, and
    | this larger image replaces it while the sidebar is expanded.
    |
    | For detailed instructions you can look the logo section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'logo' => '<b>Health</b>Nexus',
    'logo_img' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image opacity-75 shadow',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs opacity-75',
    'logo_img_alt' => 'Health Nexus Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    | Any other value falls back to the 'fullscreen' mode.
    |
    | The 'img' options configure the default preloader content, which a view
    | can replace altogether through its 'preloader' section. The 'effect' one
    | accepts the legacy 'animation__shake', 'animation__wobble',
    | 'animation__flipInX', 'animation__fadeIn', 'animation__fadeOut' and
    | 'animation__spin' tokens; any other value emits no animation. Note the
    | animation is always suppressed for the visitors who ask for reduced
    | motion.
    |
    | For detailed instructions you can look the preloader section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu, the dropdown of the
    | navbar that holds the data of the authenticated user. When it is
    | disabled, a plain logout link is rendered instead.
    |
    | The 'usermenu_header' option adds the colored header of the dropdown, and
    | 'usermenu_header_class' holds its classes (a legacy 'bg-{color}' value is
    | translated to the Bootstrap 5.3 'text-bg-{color}' helper).
    |
    | The 'usermenu_image', 'usermenu_desc' and 'usermenu_profile_url' options
    | read their value from the authenticated user model, so they require it to
    | provide, respectively, an 'adminlte_image()', an 'adminlte_desc()' and an
    | 'adminlte_profile_url()' method. A missing method is ignored.
    |
    | For detailed instructions you can look the user menu section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => true,
    'usermenu_desc' => true,
    'usermenu_profile_url' => true,

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel. Every option
    | below holds a plain url by default, or the name of a route when the
    | 'use_route_url' option is enabled. A route name that can not be resolved
    | falls back to a plain url, so a missing route does not break the panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'dashboard',
    'logout_url' => 'logout',

    // The HTTP method spoofed on the logout form. Set it to 'GET' when your
    // logout route is not a POST one, or leave it as null to post the form.

    'logout_method' => null,
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => true,

    /*
    |--------------------------------------------------------------------------
    | Assets (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | Here you can configure how the base assets of the admin panel are served.
    | AdminLTE v4 bundles Bootstrap 5.3 into its own stylesheet, but requires
    | some external resources at runtime (the Bootstrap JavaScript bundle, the
    | Bootstrap Icons font, the OverlayScrollbars plugin and the web font).
    |
    | The 'mode' option supports the next values:
    | 'local' => Serve the assets published into the public folder. When a file
    |            is not published (the third party assets require an extra step,
    |            see the artisan console commands section of the docs), the CDN
    |            location is used as a fallback when 'cdn_fallback' is enabled.
    | 'cdn'   => Always serve the assets from the configured CDN locations.
    |
    | Any other value falls back to the 'local' mode.
    |
    | Note the RTL variant of a stylesheet is picked automatically when the RTL
    | mode is active (see the 'rtl' section below).
    |
    | For detailed instructions you can look the assets section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'assets' => [

        'mode' => 'local',
        'cdn_fallback' => true,

        // The AdminLTE version used on the CDN locations below. When left as
        // null, the version installed by composer is detected and used, so
        // the assets served from the CDN always match the local ones.

        'adminlte_version' => null,

        // Load the optional extended color palette (adminlte-colors.css). It
        // provides the .bg-*, .text-bg-*, .card-* and .callout-* classes for
        // the extended set of AdminLTE colors (navy, olive, sky, ...).

        'extended_colors' => false,

        // Load the v3 color aliases (adminlte-colors-v3.css) instead of the
        // v4 palette. Useful when your markup still uses the old color names.

        'extended_colors_v3_aliases' => false,

        // Palette tuning. Both options are provided by the palette
        // stylesheets, so they require the extended colors to be enabled.
        //
        // The 'primary' option remaps the primary color of the whole template
        // to any other color of the enabled palette (for example 'teal' or
        // 'navy'). Set it to null to keep the default blue. A color the
        // enabled palette does not provide is ignored.
        //
        // The 'contrast' option applies the WCAG AA correction of the palette.
        // Only the v3 alias stylesheet ships that correction, since some of
        // its colors miss the 4.5:1 ratio. Use null to apply it automatically
        // on that palette, 'aa' to declare it in any case, or false to always
        // disable it.

        'palette' => [
            'primary' => null,
            'contrast' => null,
        ],

        // The third party resources required by the template. Set any of them
        // to false when you provide the resource on your own (for example,
        // through your Laravel asset bundling setup).

        'bootstrap_js' => true,
        'bootstrap_icons' => true,
        'overlayscrollbars' => true,

        // Paths (relative to the public folder) of the published assets.

        'local' => [
            'adminlte_css' => 'vendor/adminlte/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'vendor/adminlte/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'vendor/adminlte/dist/js/adminlte.min.js',
            'colors_css' => 'vendor/adminlte/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'vendor/bootstrap/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'vendor/bootstrap-icons/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'vendor/overlayscrollbars/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'vendor/overlayscrollbars/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'vendor/fonts/source-sans-3/index.css',
        ],

        // Locations used on the 'cdn' mode and as fallback of missing assets.

        'cdn' => [
            'adminlte_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/js/adminlte.min.js',
            'colors_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of the external web font used by the
    | AdminLTE v4 template (Source Sans 3, served from a CDN). Disabling the
    | web fonts may be useful if your admin panel internet access is
    | restricted somehow, or when you do not want the browsers of your
    | visitors to reach a third party host. The font source can be changed on
    | the 'assets' section above (the 'fonts_css' locations).
    |
    | For detailed instructions you can look the google fonts section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | Note the AdminLTE stylesheet and script are then expected to be part of
    | your own bundle, so they are not linked by the layout anymore. Any other
    | value behaves like 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'resources/css/app.css',
    'laravel_js_path' => 'resources/js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Color Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | AdminLTE v4 replaces the old 'dark mode' class by the Bootstrap 5.3
    | native 'data-bs-theme' attribute, which is set on the <html> element.
    |
    | The 'default' option supports the next values:
    | 'light' => Always start on light mode.
    | 'dark'  => Always start on dark mode.
    | 'auto'  => Follow the operating system preference of the visitor.
    |
    | Any other value falls back to the 'auto' mode.
    |
    | When 'remember' is enabled, the AdminLTE color mode widget stores the
    | visitor choice on the browser local storage (the AdminLTE v4 default
    | behavior) and the navbar widget offers the three modes above. Disable it
    | to always start with the configured default, in which case the navbar
    | widget becomes a two states toggle that persists the choice on the
    | server through the routes below.
    |
    | The 'no_flash_script' option adds a tiny inline script on the head of the
    | document to apply the resolved color mode before the first paint, and
    | thus avoid a flash of the incorrect theme.
    |
    | For detailed instructions you can look the color mode section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'color_mode' => [
        'enabled' => true,
        'default' => 'auto',
        'remember' => true,
        'no_flash_script' => true,

        // Register the color mode routes of the package (the server side dark
        // mode toggle). Set it to false when your application provides its own
        // endpoint, or when the color mode is fully client side.

        'routes' => true,

        // The color used for the 'theme-color' meta tags. These are used by
        // some browsers to colorize parts of their user interface.

        'theme_color' => [
            'light' => '#0d6efd',
            'dark' => '#1a1a1a',
        ],
    ],

    // The legacy alias of the 'color_mode.routes' option above. Set it to true
    // to unregister the color mode routes of the package.

    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | RTL Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | Here you can enable the right-to-left (RTL) support of AdminLTE v4. When
    | the RTL mode is active, the 'dir="rtl"' attribute is added to the <html>
    | element and the RTL variant of the stylesheets is loaded.
    |
    | The 'enabled' option supports the next values:
    | true  => Always use the RTL mode.
    | false => Never use the RTL mode.
    | null  => Enable the RTL mode only when the current application locale is
    |          included on the 'locales' array (auto detection).
    |
    | For detailed instructions you can look the RTL section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'rtl' => [
        'enabled' => null,
        'locales' => [
            'ar',
            'arc',
            'ckb',
            'dv',
            'fa',
            'ha',
            'he',
            'khw',
            'ks',
            'ps',
            'sd',
            'ug',
            'ur',
            'uz-AF',
            'yi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Print Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | AdminLTE tunes the printed output of the panel through the
    | 'data-lte-print' attribute of the <html> element. Here you can list the
    | tokens to declare on it, either as an array or as a space separated
    | string.
    |
    | The bundled stylesheet provides the next token:
    | 'plain' => Do not print the url after every external link, and drop the
    |            border printed around the buttons.
    |
    | The 'app' token is accepted as well, but only the AdminLTE releases whose
    | stylesheet provides it react to it (the bundled one always prints the
    | header, the sidebar and the footer).
    |
    | Leave the array empty (the default) and no attribute is emitted at all,
    | so the AdminLTE print styles apply as they ship. Any other token is
    | dropped.
    |
    | For detailed instructions you can look the layout section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'print' => [],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | The 'layout_topnav' option removes the sidebar and moves the whole
    | navigation into the top navbar. The 'layout_fixed_sidebar' option keeps
    | the sidebar in place while the content scrolls, and it is ignored on the
    | topnav layout. The 'layout_fixed_navbar' and 'layout_fixed_footer' ones
    | do the same for the navbar and the footer; note AdminLTE v4 has no
    | responsive fixed modes anymore, so an array value is read as enabled
    | whenever any of its entries is true.
    |
    | The 'layout_compact' option reduces the spacing of the layout elements.
    |
    | For detailed instructions you can look the layout section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'layout_topnav' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => true,
    'layout_compact' => false,

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel. Every
    | option holds the extra classes added to one element of the layout, and
    | they are all appended to the classes the template requires.
    |
    | Note the 'classes_topnav_nav' option is the place of the Bootstrap
    | 'navbar-expand*' class of the navbar. Removing it collapses the navbar
    | items into a single column on every viewport.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'classes_body' => 'bg-body-tertiary',
    'classes_brand' => '',
    'classes_brand_text' => 'fw-light',
    'classes_wrapper' => '',
    'classes_content_wrapper' => '',
    'classes_footer' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_content_top_area' => '',
    'classes_content_bottom_area' => '',
    'classes_sidebar' => 'bg-body-secondary shadow',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'bg-body',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container-fluid',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    // The 'data-bs-theme' value applied to the main sidebar. Use 'dark' for
    // the classic AdminLTE dark sidebar, 'light' for a light one, or null to
    // inherit the color mode of the page. Any other value is read as 'dark'.

    'sidebar_theme' => 'dark',

    // Behavior of the sidebar. The mini mode keeps the icons of a collapsed
    // sidebar visible, 'sidebar_collapse' starts the panel with the sidebar
    // already collapsed, and 'sidebar_without_hover' stops a collapsed
    // sidebar from expanding again on mouse hover.

    'sidebar_mini' => true,
    'sidebar_collapse' => false,
    'sidebar_without_hover' => false,

    // Persist the collapsed state of the sidebar on the browser, so it
    // survives a page load (the AdminLTE push menu plugin takes care of it).

    'sidebar_collapse_remember' => true,

    // The breakpoint where the sidebar turns into an overlay. AdminLTE only
    // provides the 'sm', 'md', 'lg', 'xl' and 'xxl' stylesheets, so any other
    // value falls back to 'lg'. The 'sidebar_breakpoint' option is the same
    // setting expressed as a viewport width in pixels: only the widths of
    // those breakpoints are honored (576, 768, 992, 1200 and 1400, or the
    // '.98' upper bound of their media query), any other width is ignored.
    // Leave it as null to just use the option above.

    'sidebar_expand' => 'lg',
    'sidebar_breakpoint' => null,

    // The OverlayScrollbars instance of the sidebar. The 'theme' is a class
    // name of that plugin, 'auto_hide' accepts its 'never', 'scroll', 'leave'
    // and 'move' tokens, and 'click_scroll' jumps to the clicked position of
    // the track. Any extra option of the plugin can be declared on the
    // 'sidebar_scrollbar_options' array, which is merged into (and thus wins
    // over) the three above. At or below the 'disable_below' viewport width
    // no instance is created at all, so touch scrolling is left alone.

    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'leave',
    'sidebar_scrollbar_click_scroll' => true,
    'sidebar_scrollbar_options' => [],
    'sidebar_scrollbar_disable_below' => 992,

    // The navigation menu of the sidebar. The 'aria_label' option names it for
    // the assistive technologies (null uses the translated default), the
    // 'compact', 'indent' and 'pills' options are the AdminLTE menu variants,
    // 'accordion' keeps at most one submenu open at a time and
    // 'animation_speed' is the duration, in milliseconds, of the submenu
    // animation (a value that is not a number falls back to 300).

    'sidebar_nav_aria_label' => null,
    'sidebar_nav_compact' => false,
    'sidebar_nav_indent' => false,
    'sidebar_nav_pills' => false,
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Right Sidebar (Offcanvas)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar of the admin panel. Note the old
    | AdminLTE v3 control sidebar was removed on AdminLTE v4, so the right
    | sidebar is now built on top of the Bootstrap 5 offcanvas component.
    |
    | Enable 'right_sidebar' to add its toggler to the navbar and to render the
    | panel, whose content comes from the 'right_sidebar' section of your view.
    | The 'placement' option accepts the Bootstrap 'start', 'end', 'top' and
    | 'bottom' values (any other one falls back to 'end'), the 'theme' one
    | accepts 'light' and 'dark' (null inherits the color mode of the page),
    | 'backdrop' dims the rest of the page while the panel is open and
    | 'scroll' keeps the page scrollable meanwhile. A null 'title' falls back
    | to the title of the panel, kept for the assistive technologies only.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'bi bi-gear',
    'right_sidebar_theme' => null,
    'right_sidebar_title' => null,
    'right_sidebar_placement' => 'end',
    'right_sidebar_backdrop' => true,
    'right_sidebar_scroll' => false,
    'right_sidebar_classes' => '',

    /*
    |--------------------------------------------------------------------------
    | CSS Variables (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | The AdminLTE v4 theming is driven by the Bootstrap 5.3 and the AdminLTE
    | custom properties, so overriding them is enough for most brandings and
    | needs no stylesheet of your own. The declarations are emitted on an
    | inline <style> block of the document head.
    |
    | Only well formed custom property names ('--name') and values that can
    | not break out of the declaration are accepted, any other entry is
    | silently dropped.
    |
    | For detailed instructions you can look the css variables section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    // The custom properties applied on the whole document. For example:
    // 'css_variables' => ['--bs-primary' => '#6f42c1'],

    'css_variables' => [],

    // The selector of the block above. Only ':root' and 'body' are accepted,
    // any other value falls back to ':root'.

    'css_variables_scope' => ':root',

    // The custom properties applied on the sidebar element only. AdminLTE
    // redeclares the sidebar properties under a color mode selector, so these
    // are emitted with a matching specificity. For example:
    // 'css_variables_sidebar' => ['--lte-sidebar-bg' => '#1f2d3d'],

    'css_variables_sidebar' => [],

    /*
    |--------------------------------------------------------------------------
    | Authentication Views
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    | The 'classes_auth_*' options hold the extra classes of the card of those
    | views and of its parts, and the social links below add an extra block of
    | buttons at the bottom of the login and register cards.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-primary',

    // The social login buttons of the authentication views. Every entry
    // accepts an 'url', a 'text', an 'icon' and a 'theme'. Leave the array
    // empty (the default) and no block is rendered at all. The separator is
    // the text placed above the block: null uses the translated default and
    // an empty string drops it.
    //
    // 'auth_social_links' => [
    //     [
    //         'url' => '/auth/facebook',
    //         'text' => 'Sign in using Facebook',
    //         'icon' => 'bi bi-facebook',
    //         'theme' => 'primary',
    //     ],
    // ],

    'auth_social_links' => [],
    'auth_social_links_separator' => null,

    /*
    |--------------------------------------------------------------------------
    | Lockscreen
    |--------------------------------------------------------------------------
    |
    | The AdminLTE lockscreen keeps the visitor authenticated but locks the
    | panel behind their password. Enable it to register the package routes,
    | and add the 'RedirectIfLocked' middleware to protect your own routes.
    |
    | The 'guard' option selects the authentication guard whose user provider
    | verifies the password (null uses the default guard). The 'throttle'
    | options limit the unlock attempts per user and ip, set 'max_attempts' to
    | zero to disable the limit. The 'except' option lists extra paths that
    | stay reachable while the panel is locked.
    |
    | For detailed instructions you can look the lockscreen section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/overview/authentication_views.html
    |
    */

    'lockscreen' => [
        'enabled' => false,
        'routes' => true,
        'guard' => null,

        'throttle' => [
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ],

        'except' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/menu.html
    |
    */

    'menu' => [
        // Navbar items (right side) - custom dropdowns handled via content_top_nav_right
        // Sidebar items:
        [
            'type' => 'sidebar-menu-search',
            'text' => 'Search menu...',
        ],
        [
            'type' => 'darkmode-widget',
            'topnav_right' => true,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        // Sidebar: sections follow the working day of a hospital (guideline 19.6) and docs/ux/navigation.md. Two levels where a module allows it; a section header shows only while the user can use something in it.
        [
            'text' => 'Dashboard',
            'url' => '/dashboard',
            'icon' => 'bi bi-speedometer2',
        ],
        [
            'text' => 'Today in the hospital',
            'url' => '/today',
            'icon' => 'bi bi-sun',
            'can' => 'dashboard.today.view',
        ],
        [
            'text' => 'My work',
            'module' => 'hr',
            'icon' => 'bi bi-person-badge',
            'url' => '/hr/me',
            'can' => 'hr.self.view',
        ],
        [
            'header' => 'Front desk',
            'can' => ['patients.view', 'appointments.view'],
        ],
        [
            'text' => 'Patients',
            'icon' => 'bi bi-person',
            'can' => 'patients.view',
            'submenu' => [
                [
                    'text' => 'All Patients',
                    'url' => '/patients',
                    'icon' => 'bi bi-people',
                    'can' => 'patients.view',
                ],
                [
                    'text' => 'Register Patient',
                    'url' => '/patients/create',
                    'icon' => 'bi bi-person-plus',
                    'can' => 'patients.create',
                ],
                [
                    'text' => 'Search Patients',
                    'url' => '/patients/search',
                    'icon' => 'bi bi-search',
                    'can' => 'patients.view',
                ],
                [
                    'text' => 'Duplicate Review Queue',
                    'url' => '/patients/duplicates',
                    'icon' => 'bi bi-people-fill',
                    'can' => 'patients.merge',
                ],
                [
                    'text' => 'Pending Amendments',
                    'url' => '/patients/amendments',
                    'icon' => 'bi bi-pencil-square',
                    'can' => 'patients.amend.approve',
                ],
            ],
        ],
        [
            'text' => 'Appointments',
            'module' => 'appointment',
            'icon' => 'bi bi-calendar-check',
            'can' => 'appointments.view',
            'submenu' => [
                [
                    'text' => 'All Appointments',
                    'url' => '/appointments',
                    'icon' => 'bi bi-calendar-check',
                    'can' => 'appointments.view',
                ],
                [
                    'text' => 'New Appointment',
                    'url' => '/appointments/create',
                    'icon' => 'bi bi-calendar-plus',
                    'can' => 'appointments.create',
                ],
                [
                    'text' => 'Calendar',
                    'url' => '/appointments-calendar',
                    'icon' => 'bi bi-calendar3',
                    'can' => 'appointments.view',
                ],
                [
                    'text' => 'Waiting Queue',
                    'url' => '/queue',
                    'icon' => 'bi bi-ticket',
                    'can' => 'appointments.queue',
                ],
                [
                    'text' => 'Queue board',
                    'url' => '/queue-board',
                    'icon' => 'bi bi-tv',
                    'can' => 'appointments.queue',
                ],
                [
                    'text' => 'Doctor Schedules',
                    'url' => '/schedules',
                    'icon' => 'bi bi-clock',
                    'can' => 'appointments.manage_schedule',
                ],
                [
                    'text' => 'Providers',
                    'url' => '/providers',
                    'icon' => 'bi bi-person-badge',
                    'can' => 'appointments.manage_provider',
                ],
                [
                    'text' => 'Provider Unavailability / Blocking',
                    'url' => '/provider-unavailability',
                    'icon' => 'bi bi-calendar-x',
                    'can' => 'appointments.manage_block',
                ],
                [
                    'text' => 'Holidays',
                    'url' => '/holidays',
                    'icon' => 'bi bi-calendar-event',
                    'can' => 'appointments.manage_holiday',
                ],
                [
                    'text' => 'Appointment Types',
                    'url' => '/appointment-types',
                    'icon' => 'bi bi-tags',
                    'can' => 'appointments.manage_schedule',
                ],
                [
                    'text' => 'Rooms',
                    'url' => '/appointment-rooms',
                    'icon' => 'bi bi-door-open',
                    'can' => 'appointments.manage_schedule',
                ],
            ],
        ],
        [
            'header' => 'Clinical care',
            'can' => ['encounters.view', 'ed.board.view', 'ipd.dashboard.view', 'nursing.dashboard.view', 'ot.dashboard.view', 'icu.dashboard.view'],
        ],
        [
            'text' => 'OPD (Outdoor)',
            'icon' => 'bi bi-heart-pulse',
            'can' => 'encounters.view',
            'submenu' => [
                [
                    'text' => 'Doctor Queue',
                    'url' => '/queue',
                    'icon' => 'bi bi-ticket',
                    'can' => 'queue.view',
                ],
                [
                    'text' => 'My Encounters',
                    'url' => '/encounters',
                    'icon' => 'bi bi-clipboard-data',
                    'can' => 'encounters.view',
                ],
                [
                    'text' => 'New Encounter',
                    'url' => '/encounters/create',
                    'icon' => 'bi bi-plus-circle',
                    'can' => 'encounters.create',
                ],
                [
                    'text' => 'Clinical Reports',
                    'url' => '/reports/clinical',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'encounter.export',
                ],
            ],
        ],
        [
            'text' => 'Emergency',
            'module' => 'ed',
            'icon' => 'bi bi-hospital-fill',
            'can' => 'ed.board.view',
            'submenu' => [
                ['text' => 'Tracking board', 'url' => '/emergency', 'icon' => 'bi bi-grid-1x2', 'can' => 'ed.board.view'],
                ['text' => 'My workspace', 'url' => '/emergency/workspace', 'icon' => 'bi bi-person-workspace', 'can' => 'ed.board.view'],
                ['text' => 'New arrival', 'url' => '/emergency/arrivals/create', 'icon' => 'bi bi-person-plus', 'can' => 'ed.visit.create'],
                ['text' => 'Performance', 'url' => '/emergency/dashboard', 'icon' => 'bi bi-graph-up', 'can' => 'ed.kpi.view'],
                ['text' => 'Zones and bays', 'url' => '/emergency/zones', 'icon' => 'bi bi-diagram-3', 'can' => 'ed.zone.manage'],
            ],
        ],
        [
            'text' => 'IPD (Indoor)',
            'module' => 'ipd',
            'icon' => 'bi bi-hospital',
            'can' => 'ipd.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/ipd',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'ipd.dashboard.view',
                ],
                [
                    'text' => 'Admission',
                    'icon' => 'bi bi-clipboard-check',
                    'can' => 'ipd.admission.view',
                    'submenu' => [
                        [
                            'text' => 'Admission Requests',
                            'url' => '/ipd/admission-requests',
                            'icon' => 'bi bi-file-earmark-text',
                            'can' => 'ipd.admission.view',
                        ],
                        [
                            'text' => 'Admissions',
                            'url' => '/ipd/admissions',
                            'icon' => 'bi bi-clipboard-check',
                            'can' => 'ipd.admission.view',
                        ],
                        [
                            'text' => 'Current Inpatients',
                            'url' => '/ipd/admissions?current_only=1',
                            'icon' => 'bi bi-people',
                            'can' => 'ipd.admission.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Bed Management',
                    'icon' => 'bi bi-grid-3x3-gap',
                    'can' => 'ipd.bed.view',
                    'submenu' => [
                        [
                            'text' => 'Bed Board',
                            'url' => '/ipd/bed-board',
                            'icon' => 'bi bi-grid-3x3-gap',
                            'can' => 'ipd.bed.view',
                        ],
                        [
                            'text' => 'Bed Availability',
                            'url' => '/ipd/bed-availability',
                            'icon' => 'bi bi-search',
                            'can' => 'ipd.bed.view',
                        ],
                        [
                            'text' => 'Wards',
                            'url' => '/ipd/wards',
                            'icon' => 'bi bi-building',
                            'can' => 'ipd.ward.view',
                        ],
                        [
                            'text' => 'Rooms',
                            'url' => '/ipd/rooms',
                            'icon' => 'bi bi-door-closed',
                            'can' => 'ipd.room.view',
                        ],
                        [
                            'text' => 'Beds',
                            'url' => '/ipd/beds',
                            'icon' => 'bi bi-hospital',
                            'can' => 'ipd.bed.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Transfers',
                    'url' => '/ipd/transfers',
                    'icon' => 'bi bi-arrow-left-right',
                    'can' => 'ipd.transfer.view',
                ],
                [
                    'text' => 'Patient Leave',
                    'url' => '/ipd/leave',
                    'icon' => 'bi bi-box-arrow-right',
                    'can' => 'ipd.leave.view',
                ],
                [
                    'text' => 'Discharge',
                    'url' => '/ipd/discharge',
                    'icon' => 'bi bi-house-check',
                    'can' => 'ipd.discharge.view',
                ],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'ipd.reports.view',
                    'submenu' => [
                        [
                            'text' => 'Occupancy',
                            'url' => '/ipd/reports/occupancy',
                            'icon' => 'bi bi-pie-chart',
                            'can' => 'ipd.reports.view',
                        ],
                        [
                            'text' => 'Admissions',
                            'url' => '/ipd/reports/admissions',
                            'icon' => 'bi bi-clipboard-check',
                            'can' => 'ipd.reports.view',
                        ],
                        [
                            'text' => 'Discharges',
                            'url' => '/ipd/reports/discharges',
                            'icon' => 'bi bi-house-check',
                            'can' => 'ipd.reports.view',
                        ],
                        [
                            'text' => 'Transfers',
                            'url' => '/ipd/reports/transfers',
                            'icon' => 'bi bi-arrow-left-right',
                            'can' => 'ipd.reports.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Settings',
                    'url' => '/ipd/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'ipd.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Nursing',
            'module' => 'nursing',
            'icon' => 'bi bi-clipboard2-pulse',
            'can' => 'nursing.dashboard.view',
            'submenu' => [
                ['text' => 'Dashboard', 'url' => '/nursing', 'icon' => 'bi bi-speedometer2', 'can' => 'nursing.dashboard.view'],
                ['text' => 'Ward Patients', 'url' => '/nursing/episodes', 'icon' => 'bi bi-people', 'can' => 'nursing.dashboard.view'],
                ['text' => 'Tasks', 'url' => '/nursing/tasks', 'icon' => 'bi bi-check2-square', 'can' => 'nursing.task.view'],
                ['text' => 'Medication (MAR)', 'url' => '/nursing/mar', 'icon' => 'bi bi-capsule', 'can' => 'nursing.mar.view'],
                ['text' => 'Handover', 'url' => '/nursing/handover', 'icon' => 'bi bi-arrow-left-right', 'can' => 'nursing.handover.view'],
                ['text' => 'Escalations', 'url' => '/nursing/escalations', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'nursing.escalation.view'],
                ['text' => 'Shifts', 'url' => '/nursing/shifts', 'icon' => 'bi bi-clock', 'can' => 'nursing.shift.view'],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'nursing.reports.view',
                    'submenu' => [
                        ['text' => 'Workload', 'url' => '/nursing/reports/workload', 'icon' => 'bi bi-person-workspace', 'can' => 'nursing.reports.view'],
                        ['text' => 'Medication', 'url' => '/nursing/reports/medication', 'icon' => 'bi bi-capsule-pill', 'can' => 'nursing.reports.view'],
                        ['text' => 'Quality', 'url' => '/nursing/reports/quality', 'icon' => 'bi bi-award', 'can' => 'nursing.reports.view'],
                    ],
                ],
                ['text' => 'Settings', 'url' => '/nursing/settings', 'icon' => 'bi bi-gear', 'can' => 'nursing.settings.manage'],
            ],
        ],
        [
            'text' => 'OT / Surgery',
            'module' => 'ot',
            'icon' => 'bi bi-scissors',
            'can' => 'ot.dashboard.view',
            'submenu' => [
                ['text' => 'Dashboard', 'url' => '/ot', 'icon' => 'bi bi-speedometer2', 'can' => 'ot.dashboard.view'],
                ['text' => 'Surgery Requests', 'url' => '/ot/surgery-requests', 'icon' => 'bi bi-file-medical', 'can' => 'ot.surgery.view'],
                ['text' => 'Surgical Cases', 'url' => '/ot/surgeries', 'icon' => 'bi bi-clipboard2-pulse', 'can' => 'ot.surgery.view'],
                ['text' => 'Theatres', 'url' => '/ot/theatres', 'icon' => 'bi bi-door-open', 'can' => 'ot.theatre.view'],
                ['text' => 'Procedures', 'url' => '/ot/procedures', 'icon' => 'bi bi-list-check', 'can' => 'ot.procedure.view'],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'ot.reports.view',
                    'submenu' => [
                        ['text' => 'Surgeries', 'url' => '/ot/reports/surgeries', 'icon' => 'bi bi-clipboard-data', 'can' => 'ot.reports.view'],
                        ['text' => 'Utilization', 'url' => '/ot/reports/utilization', 'icon' => 'bi bi-speedometer', 'can' => 'ot.reports.view'],
                        ['text' => 'Complications', 'url' => '/ot/reports/complications', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'ot.reports.view'],
                    ],
                ],
                ['text' => 'Settings', 'url' => '/ot/settings', 'icon' => 'bi bi-gear', 'can' => 'ot.settings.manage'],
            ],
        ],
        [
            'text' => 'ICU / Critical Care',
            'module' => 'icu',
            'icon' => 'bi bi-heart-pulse',
            'can' => 'icu.dashboard.view',
            'submenu' => [
                ['text' => 'Dashboard', 'url' => '/icu', 'icon' => 'bi bi-speedometer2', 'can' => 'icu.dashboard.view'],
                ['text' => 'Bed Board', 'url' => '/icu/bed-board', 'icon' => 'bi bi-grid-3x3-gap', 'can' => 'icu.bed.view'],
                ['text' => 'Admission Requests', 'url' => '/icu/admission-requests', 'icon' => 'bi bi-file-medical', 'can' => 'icu.admission_request.view'],
                ['text' => 'Patients / Episodes', 'url' => '/icu/episodes', 'icon' => 'bi bi-clipboard2-pulse', 'can' => 'icu.episode.view'],
                ['text' => 'Critical Alerts', 'url' => '/icu/alerts', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'icu.alert.view'],
                ['text' => 'Code Blue / Critical Events', 'url' => '/icu/critical-events', 'icon' => 'bi bi-activity', 'can' => 'icu.critical_event.view'],
                ['text' => 'Procedures', 'url' => '/icu/procedures', 'icon' => 'bi bi-list-check', 'can' => 'icu.procedure.view'],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'icu.reports.view',
                    'submenu' => [
                        ['text' => 'Occupancy', 'url' => '/icu/reports/occupancy', 'icon' => 'bi bi-clipboard-data', 'can' => 'icu.reports.view'],
                        ['text' => 'Utilization', 'url' => '/icu/reports/utilization', 'icon' => 'bi bi-speedometer', 'can' => 'icu.reports.view'],
                        ['text' => 'Outcomes', 'url' => '/icu/reports/outcomes', 'icon' => 'bi bi-graph-up', 'can' => 'icu.reports.view'],
                    ],
                ],
                ['text' => 'Settings', 'url' => '/icu/settings', 'icon' => 'bi bi-gear', 'can' => 'icu.settings.manage'],
            ],
        ],
        [
            'header' => 'Diagnostics and pharmacy',
            'can' => ['lab.dashboard.view', 'radiology.dashboard.view', 'pharmacy.dashboard.view', 'bloodbank.dashboard.view'],
            'modules' => ['lab', 'radiology', 'pharmacy', 'bloodbank'],
        ],
        [
            'text' => 'Laboratory (Pathology)',
            'module' => 'lab',
            'icon' => 'bi bi-clipboard2-pulse',
            'can' => 'lab.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/lab',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'lab.dashboard.view',
                ],
                [
                    'text' => 'Orders',
                    'icon' => 'bi bi-clipboard-check',
                    'can' => 'lab.order.view',
                    'submenu' => [
                        [
                            'text' => 'Lab Orders',
                            'url' => '/lab/orders',
                            'icon' => 'bi bi-clipboard-check',
                            'can' => 'lab.order.view',
                        ],
                        [
                            'text' => 'Worklist',
                            'url' => '/lab/worklist',
                            'icon' => 'bi bi-list-task',
                            'can' => 'lab.result.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Specimens',
                    'icon' => 'bi bi-droplet',
                    'can' => 'lab.specimen.view',
                    'submenu' => [
                        [
                            'text' => 'Specimens',
                            'url' => '/lab/specimens',
                            'icon' => 'bi bi-droplet',
                            'can' => 'lab.specimen.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Results',
                    'icon' => 'bi bi-clipboard-data',
                    'can' => 'lab.result.view',
                    'submenu' => [
                        [
                            'text' => 'Critical Results',
                            'url' => '/lab/critical-results',
                            'icon' => 'bi bi-exclamation-triangle',
                            'can' => 'lab.critical_result.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-file-earmark-medical',
                    'can' => 'lab.report.view',
                    'submenu' => [
                        [
                            'text' => 'Lab Reports',
                            'url' => '/lab/reports',
                            'icon' => 'bi bi-file-earmark-medical',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Daily Volume',
                            'url' => '/lab/reports-analytics/daily-volume',
                            'icon' => 'bi bi-bar-chart',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Sample Rejection',
                            'url' => '/lab/reports-analytics/sample-rejection',
                            'icon' => 'bi bi-x-circle',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Critical Result Report',
                            'url' => '/lab/reports-analytics/critical-results',
                            'icon' => 'bi bi-exclamation-triangle',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Amendments Report',
                            'url' => '/lab/reports-analytics/result-amendments',
                            'icon' => 'bi bi-pencil-square',
                            'can' => 'lab.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Test Catalog',
                    'icon' => 'bi bi-journal-medical',
                    'can' => 'lab.test.view',
                    'submenu' => [
                        [
                            'text' => 'Tests',
                            'url' => '/lab/tests',
                            'icon' => 'bi bi-journal-medical',
                            'can' => 'lab.test.view',
                        ],
                        [
                            'text' => 'Panels',
                            'url' => '/lab/panels',
                            'icon' => 'bi bi-collection',
                            'can' => 'lab.test.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Quality Control',
                    'url' => '/lab/qc',
                    'icon' => 'bi bi-patch-check',
                    'can' => 'lab.qc.view',
                ],
                [
                    'text' => 'Analyzers',
                    'url' => '/lab/analyzers',
                    'icon' => 'bi bi-hdd-network',
                    'can' => 'lab.analyzer.view',
                ],
                [
                    'text' => 'Settings',
                    'url' => '/lab/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'lab.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Radiology and imaging',
            'module' => 'radiology',
            'icon' => 'bi bi-radioactive',
            'can' => 'radiology.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/radiology',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'radiology.dashboard.view',
                ],
                [
                    'text' => 'Orders',
                    'icon' => 'bi bi-clipboard-check',
                    'can' => 'radiology.order.view',
                    'submenu' => [
                        [
                            'text' => 'Radiology Orders',
                            'url' => '/radiology/orders',
                            'icon' => 'bi bi-clipboard-check',
                            'can' => 'radiology.order.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Scheduling',
                    'icon' => 'bi bi-calendar-week',
                    'can' => 'radiology.examination.view',
                    'submenu' => [
                        [
                            'text' => 'Examination Queue',
                            'url' => '/radiology/examinations',
                            'icon' => 'bi bi-calendar-week',
                            'can' => 'radiology.examination.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Studies',
                    'url' => '/radiology/studies',
                    'icon' => 'bi bi-images',
                    'can' => 'radiology.study.view',
                ],
                [
                    'text' => 'Radiologist Worklist',
                    'url' => '/radiology/worklist',
                    'icon' => 'bi bi-list-task',
                    'can' => 'radiology.worklist.view',
                ],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-file-earmark-medical',
                    'can' => 'radiology.report.view',
                    'submenu' => [
                        [
                            'text' => 'Radiology Reports',
                            'url' => '/radiology/reports',
                            'icon' => 'bi bi-file-earmark-medical',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Daily Volume',
                            'url' => '/radiology/analytics/daily-volume',
                            'icon' => 'bi bi-bar-chart',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Modality Utilization',
                            'url' => '/radiology/analytics/modality-utilization',
                            'icon' => 'bi bi-pie-chart',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Turnaround Time',
                            'url' => '/radiology/analytics/turnaround-time',
                            'icon' => 'bi bi-stopwatch',
                            'can' => 'radiology.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Critical Findings',
                    'url' => '/radiology/critical-findings',
                    'icon' => 'bi bi-exclamation-triangle',
                    'can' => 'radiology.critical_finding.view',
                ],
                [
                    'text' => 'Catalog',
                    'icon' => 'bi bi-journal-medical',
                    'can' => 'radiology.procedure.view',
                    'submenu' => [
                        [
                            'text' => 'Procedures',
                            'url' => '/radiology/procedures',
                            'icon' => 'bi bi-journal-medical',
                            'can' => 'radiology.procedure.view',
                        ],
                        [
                            'text' => 'Modalities',
                            'url' => '/radiology/modalities',
                            'icon' => 'bi bi-cpu',
                            'can' => 'radiology.modality.view',
                        ],
                    ],
                ],
                [
                    'text' => 'PACS',
                    'url' => '/radiology/pacs',
                    'icon' => 'bi bi-hdd-network',
                    'can' => 'radiology.pacs.view',
                ],
                [
                    'text' => 'Settings',
                    'url' => '/radiology/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'radiology.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Pharmacy',
            'module' => 'pharmacy',
            'icon' => 'bi bi-capsule',
            'can' => 'pharmacy.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/pharmacy',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'pharmacy.dashboard.view',
                ],
                [
                    'text' => 'Orders',
                    'url' => '/pharmacy/orders',
                    'icon' => 'bi bi-clipboard-check',
                    'can' => 'pharmacy.prescription.view',
                ],
                [
                    'text' => 'Dispensing',
                    'url' => '/pharmacy/dispensing',
                    'icon' => 'bi bi-prescription2',
                    'can' => 'pharmacy.dispensing.view',
                ],
                [
                    'text' => 'Catalog',
                    'icon' => 'bi bi-journal-medical',
                    'can' => 'pharmacy.medication.view',
                    'submenu' => [
                        [
                            'text' => 'Medications',
                            'url' => '/pharmacy/medications',
                            'icon' => 'bi bi-capsule',
                            'can' => 'pharmacy.medication.view',
                        ],
                        [
                            'text' => 'Generics',
                            'url' => '/pharmacy/generics',
                            'icon' => 'bi bi-journal-medical',
                            'can' => 'pharmacy.generic.view',
                        ],
                        [
                            'text' => 'Brands',
                            'url' => '/pharmacy/brands',
                            'icon' => 'bi bi-tag',
                            'can' => 'pharmacy.brand.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Inventory',
                    'icon' => 'bi bi-box-seam',
                    'can' => 'pharmacy.stock.view',
                    'submenu' => [
                        [
                            'text' => 'Stock',
                            'url' => '/pharmacy/stock',
                            'icon' => 'bi bi-box-seam',
                            'can' => 'pharmacy.stock.view',
                        ],
                        [
                            'text' => 'Batches',
                            'url' => '/pharmacy/batches',
                            'icon' => 'bi bi-upc-scan',
                            'can' => 'pharmacy.batch.view',
                        ],
                        [
                            'text' => 'Transfers',
                            'url' => '/pharmacy/transfers',
                            'icon' => 'bi bi-arrow-left-right',
                            'can' => 'pharmacy.stock.transfer',
                        ],
                        [
                            'text' => 'Stock Counts',
                            'url' => '/pharmacy/stock-counts',
                            'icon' => 'bi bi-clipboard-data',
                            'can' => 'pharmacy.stock.count',
                        ],
                    ],
                ],
                [
                    'text' => 'Quarantine',
                    'url' => '/pharmacy/quarantine',
                    'icon' => 'bi bi-exclamation-octagon',
                    'can' => 'pharmacy.quarantine.manage',
                ],
                [
                    'text' => 'Recalls',
                    'url' => '/pharmacy/recalls',
                    'icon' => 'bi bi-arrow-counterclockwise',
                    'can' => 'pharmacy.recall.view',
                ],
                [
                    'text' => 'Safety Alerts',
                    'url' => '/pharmacy/safety-alerts',
                    'icon' => 'bi bi-exclamation-triangle',
                    'can' => 'pharmacy.safety_alert.view',
                ],
                [
                    'text' => 'Controlled Drugs',
                    'url' => '/pharmacy/controlled-drugs',
                    'icon' => 'bi bi-shield-lock',
                    'can' => 'pharmacy.controlled_drug.view',
                ],
                [
                    'text' => 'Settings',
                    'url' => '/pharmacy/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'pharmacy.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Blood bank',
            'module' => 'bloodbank',
            'icon' => 'bi bi-droplet-fill',
            'can' => 'bloodbank.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/blood-bank', 'icon' => 'bi bi-speedometer2', 'can' => 'bloodbank.dashboard.view'],
                ['text' => 'Requests', 'url' => '/blood-bank/requests', 'icon' => 'bi bi-clipboard2-pulse', 'can' => 'bloodbank.request.view'],
                ['text' => 'Transfusions', 'url' => '/blood-bank/transfusions', 'icon' => 'bi bi-activity', 'can' => 'bloodbank.transfusion.view'],
                ['text' => 'Blood stock', 'url' => '/blood-bank/inventory', 'icon' => 'bi bi-boxes', 'can' => 'bloodbank.inventory.view'],
                ['text' => 'Donors', 'url' => '/blood-bank/donors', 'icon' => 'bi bi-people', 'can' => 'bloodbank.donor.view'],
                ['text' => 'Lookback and recall', 'url' => '/blood-bank/lookback', 'icon' => 'bi bi-diagram-3', 'can' => 'bloodbank.lookback.view'],
                ['text' => 'Incidents', 'url' => '/blood-bank/incidents', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'bloodbank.incident.view'],
                ['text' => 'Use and wastage', 'url' => '/blood-bank/reports', 'icon' => 'bi bi-bar-chart', 'can' => 'bloodbank.report.view'],
            ],
        ],
        [
            'header' => 'Billing and insurance',
            'can' => ['billing.dashboard.view', 'insurance.dashboard.view', 'welfare.dashboard.view', 'welfare.assessment.view', 'welfare.grant.view', 'welfare.fund.view', 'rates.bed.view', 'rates.opd.view'],
        ],
        [
            'text' => 'Billing and cashier',
            'icon' => 'bi bi-cash-coin',
            'can' => 'billing.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/billing',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'billing.dashboard.view',
                ],
                [
                    'text' => 'Billing',
                    'icon' => 'bi bi-receipt',
                    'can' => 'billing.invoice.view',
                    'submenu' => [
                        [
                            'text' => 'New Invoice',
                            'url' => '/billing/invoices/create',
                            'icon' => 'bi bi-plus-circle',
                            'can' => 'billing.invoice.create',
                        ],
                        [
                            'text' => 'Invoices',
                            'url' => '/billing/invoices',
                            'icon' => 'bi bi-receipt',
                            'can' => 'billing.invoice.view',
                        ],
                        [
                            'text' => 'Charges',
                            'url' => '/billing/charges',
                            'icon' => 'bi bi-list-check',
                            'can' => 'billing.charge.view',
                        ],
                        [
                            'text' => 'Adjustments',
                            'url' => '/billing/adjustments',
                            'icon' => 'bi bi-sliders',
                            'can' => 'billing.adjustment.request',
                        ],
                        [
                            'text' => 'Outstanding',
                            'url' => '/billing/reports/receivables',
                            'icon' => 'bi bi-exclamation-circle',
                            'can' => 'billing.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Payments',
                    'icon' => 'bi bi-credit-card',
                    'can' => 'billing.payment.view',
                    'submenu' => [
                        [
                            'text' => 'Collect Payment',
                            'url' => '/billing/payments/create',
                            'icon' => 'bi bi-cash',
                            'can' => 'billing.payment.create',
                        ],
                        [
                            'text' => 'Payments',
                            'url' => '/billing/payments',
                            'icon' => 'bi bi-credit-card',
                            'can' => 'billing.payment.view',
                        ],
                        [
                            'text' => 'Receipts',
                            'url' => '/billing/receipts',
                            'icon' => 'bi bi-file-earmark-text',
                            'can' => 'billing.receipt.view',
                        ],
                        [
                            'text' => 'Refunds',
                            'url' => '/billing/refunds',
                            'icon' => 'bi bi-arrow-counterclockwise',
                            'can' => 'billing.refund.request',
                        ],
                    ],
                ],
                [
                    'text' => 'Cashier',
                    'icon' => 'bi bi-shop',
                    'can' => 'billing.cashier.view',
                    'submenu' => [
                        [
                            'text' => 'My Session',
                            'url' => '/billing/cashier/my-session',
                            'icon' => 'bi bi-person-badge',
                            'can' => 'billing.cashier.view',
                        ],
                        [
                            'text' => 'Open Session',
                            'url' => '/billing/cashier/open',
                            'icon' => 'bi bi-door-open',
                            'can' => 'billing.cashier.open',
                        ],
                        [
                            'text' => 'All Sessions',
                            'url' => '/billing/cashier',
                            'icon' => 'bi bi-list-ul',
                            'can' => 'billing.cashier.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Pricing',
                    'icon' => 'bi bi-tags',
                    'can' => 'billing.pricing.view',
                    'submenu' => [
                        [
                            'text' => 'Service Catalog',
                            'url' => '/billing/items',
                            'icon' => 'bi bi-list-ul',
                            'can' => 'billing.pricing.view',
                        ],
                        [
                            'text' => 'Categories',
                            'url' => '/billing/categories',
                            'icon' => 'bi bi-diagram-3',
                            'can' => 'billing.pricing.view',
                        ],
                        [
                            'text' => 'Price Lists',
                            'url' => '/billing/price-lists',
                            'icon' => 'bi bi-currency-exchange',
                            'can' => 'billing.pricing.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Corporate',
                    'icon' => 'bi bi-building-gear',
                    'can' => 'billing.corporate.view',
                    'submenu' => [
                        [
                            'text' => 'Organizations',
                            'url' => '/billing/corporates',
                            'icon' => 'bi bi-building',
                            'can' => 'billing.corporate.view',
                        ],
                        [
                            'text' => 'Receivables',
                            'url' => '/billing/corporates/receivables',
                            'icon' => 'bi bi-cash-stack',
                            'can' => 'billing.corporate.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Insurance',
                    'icon' => 'bi bi-shield-plus',
                    'can' => 'insurance.view',
                    'submenu' => [
                        [
                            'text' => 'Providers',
                            'url' => '/billing/insurance/providers',
                            'icon' => 'bi bi-shield-check',
                            'can' => 'insurance.view',
                        ],
                        [
                            'text' => 'Policies',
                            'url' => '/billing/insurance/policies',
                            'icon' => 'bi bi-file-earmark-medical',
                            'can' => 'billing.insurance.policy.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Reports',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'billing.report.view',
                    'submenu' => [
                        [
                            'text' => 'Billing Report',
                            'url' => '/billing/reports/billing',
                            'icon' => 'bi bi-file-bar-graph',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Collection Report',
                            'url' => '/billing/reports/collection',
                            'icon' => 'bi bi-cash-coin',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Revenue Report',
                            'url' => '/billing/reports/revenue',
                            'icon' => 'bi bi-graph-up',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Receivables Report',
                            'url' => '/billing/reports/receivables',
                            'icon' => 'bi bi-hourglass-split',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Refunds Report',
                            'url' => '/billing/reports/refunds',
                            'icon' => 'bi bi-arrow-counterclockwise',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Discounts Report',
                            'url' => '/billing/reports/discounts',
                            'icon' => 'bi bi-percent',
                            'can' => 'billing.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Settings',
                    'url' => '/billing/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'billing.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Insurance and corporate',
            'module' => 'insurance',
            'icon' => 'bi bi-shield-check',
            'can' => 'insurance.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/insurance', 'icon' => 'bi bi-speedometer2', 'can' => 'insurance.dashboard.view'],
                ['text' => 'Claims', 'url' => '/insurance/claims', 'icon' => 'bi bi-file-earmark-text', 'can' => 'insurance.claim.view'],
                ['text' => 'Payments from payers', 'url' => '/insurance/remittances', 'icon' => 'bi bi-cash-coin', 'can' => 'insurance.remittance.view'],
                ['text' => 'Pre-authorizations', 'url' => '/insurance/preauths', 'icon' => 'bi bi-patch-check', 'can' => 'insurance.preauth.view'],
                ['text' => 'Patient policies', 'url' => '/insurance/policies', 'icon' => 'bi bi-person-vcard', 'can' => 'insurance.policy.view'],
                ['text' => 'Payers and plans', 'url' => '/insurance/payers', 'icon' => 'bi bi-building', 'can' => 'insurance.payer.view'],
                ['text' => 'Corporate clients', 'url' => '/insurance/corporate', 'icon' => 'bi bi-briefcase', 'can' => 'insurance.corporate.view'],
                ['text' => 'Denials to work', 'url' => '/insurance/worklist', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'insurance.claim.view'],
            ],
        ],
        [
            'text' => 'Bed and cabin rates',
            'module' => 'rates',
            'url' => '/rates/beds',
            'icon' => 'bi bi-hospital',
            'can' => 'rates.bed.view',
        ],
        [
            'text' => 'OPD ticket and fees',
            'module' => 'rates',
            'url' => '/rates/opd',
            'icon' => 'bi bi-ticket-perforated',
            'can' => 'rates.opd.view',
        ],
        [
            'text' => 'Patient welfare',
            'icon' => 'bi bi-heart-pulse-fill',
            'module' => 'welfare',
            'can' => ['welfare.dashboard.view', 'welfare.assessment.view', 'welfare.grant.view', 'welfare.fund.view'],
            'submenu' => [
                ['text' => 'Welfare overview', 'url' => '/welfare', 'icon' => 'bi bi-speedometer2', 'can' => 'welfare.dashboard.view'],
                ['text' => 'Assessments', 'url' => '/welfare/assessments', 'icon' => 'bi bi-clipboard2-check', 'can' => 'welfare.assessment.view'],
                ['text' => 'Grants', 'url' => '/welfare/grants', 'icon' => 'bi bi-gift', 'can' => 'welfare.grant.view'],
                ['text' => 'Funds and donations', 'url' => '/welfare/funds', 'icon' => 'bi bi-piggy-bank', 'can' => 'welfare.fund.view'],
                ['text' => 'Welfare reports', 'url' => '/welfare/reports', 'icon' => 'bi bi-bar-chart', 'can' => 'welfare.report.view'],
            ],
        ],
        [
            'header' => 'Supply chain',
            'can' => ['inventory.dashboard.view', 'procurement.po.view'],
            'modules' => ['inventory'],
        ],
        [
            'text' => 'Stores and supplies',
            'module' => 'inventory',
            'icon' => 'bi bi-boxes',
            'can' => 'inventory.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/inventory', 'icon' => 'bi bi-speedometer2', 'can' => 'inventory.dashboard.view'],
                ['text' => 'Stock on hand', 'url' => '/inventory/stock', 'icon' => 'bi bi-box-seam', 'can' => 'inventory.stock.view'],
                ['text' => 'Items', 'url' => '/inventory/items', 'icon' => 'bi bi-tags', 'can' => 'inventory.item.view'],
                ['text' => 'Stores', 'url' => '/inventory/stores', 'icon' => 'bi bi-building', 'can' => 'inventory.store.view'],
                ['text' => 'Department requests', 'url' => '/inventory/requests', 'icon' => 'bi bi-clipboard-check', 'can' => 'inventory.request.view'],
                ['text' => 'Transfers', 'url' => '/inventory/transfers', 'icon' => 'bi bi-arrow-left-right', 'can' => 'inventory.transfer.view'],
                ['text' => 'Use of supplies', 'url' => '/inventory/consumption', 'icon' => 'bi bi-journal-minus', 'can' => 'inventory.consumption.record'],
                ['text' => 'Expiry and recalls', 'url' => '/inventory/expiry', 'icon' => 'bi bi-hourglass-split', 'can' => 'inventory.expiry.view'],
                ['text' => 'Stock counts', 'url' => '/inventory/counts', 'icon' => 'bi bi-list-check', 'can' => 'inventory.count.view'],
                ['text' => 'Adjustments', 'url' => '/inventory/adjustments', 'icon' => 'bi bi-pencil-square', 'can' => 'inventory.adjustment.view'],
                ['text' => 'Stock reports', 'url' => '/inventory/reports', 'icon' => 'bi bi-bar-chart', 'can' => 'inventory.report.view'],
            ],
        ],
        [
            'text' => 'Purchasing',
            'module' => 'inventory',
            'icon' => 'bi bi-cart-check',
            'can' => 'procurement.po.view',
            'submenu' => [
                ['text' => 'What to buy next', 'url' => '/procurement/reorder', 'icon' => 'bi bi-lightbulb', 'can' => 'procurement.requisition.view'],
                ['text' => 'Requisitions', 'url' => '/procurement/requisitions', 'icon' => 'bi bi-card-checklist', 'can' => 'procurement.requisition.view'],
                ['text' => 'Quotations', 'url' => '/procurement/rfqs', 'icon' => 'bi bi-columns-gap', 'can' => 'procurement.rfq.view'],
                ['text' => 'Purchase orders', 'url' => '/procurement/orders', 'icon' => 'bi bi-file-earmark-text', 'can' => 'procurement.po.view'],
                ['text' => 'Goods received', 'url' => '/procurement/receipts', 'icon' => 'bi bi-box-arrow-in-down', 'can' => 'procurement.grn.view'],
                ['text' => 'Supplier invoices', 'url' => '/procurement/invoices', 'icon' => 'bi bi-receipt', 'can' => 'procurement.invoice.view'],
                ['text' => 'Goods sent back', 'url' => '/procurement/returns', 'icon' => 'bi bi-arrow-return-left', 'can' => 'procurement.return.manage'],
                ['text' => 'Suppliers', 'url' => '/procurement/suppliers', 'icon' => 'bi bi-truck', 'can' => 'procurement.supplier.view'],
                ['text' => 'Budgets', 'url' => '/procurement/budgets', 'icon' => 'bi bi-piggy-bank', 'can' => 'procurement.budget.view'],
            ],
        ],
        [
            'header' => 'People and payroll',
            'can' => ['hr.employee.view', 'hr.roster.view', 'payroll.run.view'],
            'modules' => ['hr'],
        ],
        [
            'text' => 'Staff records',
            'module' => 'hr',
            'icon' => 'bi bi-people',
            'can' => 'hr.employee.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/hr', 'icon' => 'bi bi-speedometer2', 'can' => 'hr.dashboard.view'],
                ['text' => 'Employees', 'url' => '/hr/employees', 'icon' => 'bi bi-person-lines-fill', 'can' => 'hr.employee.view'],
                ['text' => 'Jobs and positions', 'url' => '/hr/jobs', 'icon' => 'bi bi-diagram-3', 'can' => 'hr.employee.view'],
                ['text' => 'Doctors and employees', 'url' => '/hr/provider-links', 'icon' => 'bi bi-link-45deg', 'can' => 'hr.provider_link.review'],
                ['text' => 'Vacancies', 'url' => '/hr/vacancies', 'icon' => 'bi bi-megaphone', 'can' => 'hr.recruitment.manage'],
                ['text' => 'Training and appraisals', 'url' => '/hr/training', 'icon' => 'bi bi-mortarboard', 'can' => 'hr.training.manage'],
                ['text' => 'People reports', 'url' => '/hr/reports', 'icon' => 'bi bi-bar-chart', 'can' => 'hr.report.view'],
            ],
        ],
        [
            'text' => 'Duty roster and leave',
            'module' => 'hr',
            'icon' => 'bi bi-calendar-week',
            'can' => 'hr.roster.view',
            'submenu' => [
                ['text' => 'Duty roster', 'url' => '/hr/roster', 'icon' => 'bi bi-calendar3', 'can' => 'hr.roster.view'],
                ['text' => 'Shifts and staffing needs', 'url' => '/hr/shifts', 'icon' => 'bi bi-clock', 'can' => 'hr.roster.view'],
                ['text' => 'Attendance', 'url' => '/hr/attendance', 'icon' => 'bi bi-fingerprint', 'can' => 'attendance.view'],
                ['text' => 'Attendance corrections', 'url' => '/hr/corrections', 'icon' => 'bi bi-pencil-square', 'can' => 'attendance.regularize.approve'],
                ['text' => 'Leave requests', 'url' => '/hr/leave', 'icon' => 'bi bi-airplane', 'can' => 'leave.request.view'],
                ['text' => 'Leave balances', 'url' => '/hr/leave-balances', 'icon' => 'bi bi-journal-check', 'can' => 'leave.balance.view'],
                ['text' => 'Leave types', 'url' => '/hr/leave-types', 'icon' => 'bi bi-card-list', 'can' => 'leave.type.manage'],
                ['text' => 'Overtime', 'url' => '/hr/overtime', 'icon' => 'bi bi-moon-stars', 'can' => 'payroll.overtime.approve'],
            ],
        ],
        [
            'text' => 'Payroll',
            'module' => 'hr',
            'icon' => 'bi bi-cash-stack',
            'can' => 'payroll.run.view',
            'submenu' => [
                ['text' => 'Payroll runs', 'url' => '/hr/payroll', 'icon' => 'bi bi-calculator', 'can' => 'payroll.run.view'],
                ['text' => 'Pay set-up', 'url' => '/hr/compensation', 'icon' => 'bi bi-sliders', 'can' => 'compensation.structure.view'],
            ],
        ],
        [
            'header' => 'Finance',
            'can' => ['finance.dashboard.view'],
            'modules' => ['finance'],
        ],
        [
            'text' => 'Finance',
            'module' => 'finance',
            'icon' => 'bi bi-journal-bookmark',
            'can' => 'finance.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/finance', 'icon' => 'bi bi-speedometer2', 'can' => 'finance.dashboard.view'],
                ['text' => 'Journals', 'url' => '/finance/journals', 'icon' => 'bi bi-journal-text', 'can' => 'finance.journal.view'],
                ['text' => 'Who owes us', 'url' => '/finance/receivables', 'icon' => 'bi bi-person-lines-fill', 'can' => 'finance.receivable.view'],
                ['text' => 'What we owe suppliers', 'url' => '/finance/payables', 'icon' => 'bi bi-truck', 'can' => 'finance.payable.view'],
                ['text' => 'Bank, cash and wallets', 'url' => '/finance/banks', 'icon' => 'bi bi-bank', 'can' => 'finance.bank.view'],
                ['text' => 'Banking the tills', 'url' => '/finance/deposits', 'icon' => 'bi bi-cash-stack', 'can' => 'finance.bank.view'],
                ['text' => 'Bank statements', 'url' => '/finance/statements', 'icon' => 'bi bi-file-earmark-spreadsheet', 'can' => 'finance.bank.view'],
                ['text' => 'Trial balance', 'url' => '/finance/trial-balance', 'icon' => 'bi bi-list-columns', 'can' => 'finance.ledger.view'],
                ['text' => 'Income statement', 'url' => '/finance/income-statement', 'icon' => 'bi bi-graph-up', 'can' => 'finance.report.view'],
                ['text' => 'Balance sheet', 'url' => '/finance/balance-sheet', 'icon' => 'bi bi-columns-gap', 'can' => 'finance.report.view'],
                ['text' => 'Cash flow', 'url' => '/finance/cash-flow', 'icon' => 'bi bi-arrow-left-right', 'can' => 'finance.report.view'],
                ['text' => 'Profit by department', 'url' => '/finance/profitability', 'icon' => 'bi bi-diagram-2', 'can' => 'finance.report.view'],
                ['text' => 'Doctor revenue and fee share', 'url' => '/finance/doctors', 'icon' => 'bi bi-person-badge', 'can' => 'finance.report.view'],
                ['text' => 'VAT return worksheet', 'url' => '/finance/tax-worksheet', 'icon' => 'bi bi-percent', 'can' => 'finance.tax.view'],
                ['text' => 'Tax register', 'url' => '/finance/tax-register', 'icon' => 'bi bi-receipt', 'can' => 'finance.tax.view'],
                ['text' => 'Budgets', 'url' => '/finance/budgets', 'icon' => 'bi bi-piggy-bank', 'can' => 'finance.budget.view'],
                ['text' => 'Fixed assets', 'url' => '/finance/assets', 'icon' => 'bi bi-boxes', 'can' => 'finance.asset.view'],
                ['text' => 'Posting problems', 'url' => '/finance/postings', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'finance.posting.view'],
                ['text' => 'Chart of accounts', 'url' => '/finance/accounts', 'icon' => 'bi bi-diagram-3', 'can' => 'finance.chart.view'],
                ['text' => 'Where things are posted', 'url' => '/finance/roles', 'icon' => 'bi bi-signpost-split', 'can' => 'finance.chart.view'],
                ['text' => 'Cost centres', 'url' => '/finance/cost-centers', 'icon' => 'bi bi-building', 'can' => 'finance.chart.view'],
                ['text' => 'Tax codes', 'url' => '/finance/tax-codes', 'icon' => 'bi bi-percent', 'can' => 'finance.tax.view'],
                ['text' => 'Fiscal years and periods', 'url' => '/finance/years', 'icon' => 'bi bi-calendar3', 'can' => 'finance.chart.view'],
                ['text' => 'Finance settings', 'url' => '/finance/settings', 'icon' => 'bi bi-gear', 'can' => 'finance.settings.manage'],
            ],
        ],
        [
            'header' => 'Hospital services',
            'can' => ['facility.dashboard.view', 'facility.workorder.report', 'facility.housekeeping.view', 'facility.visitor.gate', 'facility.diet.view', 'facility.mortuary.view', 'quality.incident.report', 'quality.dashboard.view', 'crm.dashboard.view', 'portal.dashboard.view', 'vital.dashboard.view', 'vital.birth.view', 'vital.death.view', 'vital.medicolegal.view', 'mch.dashboard.view', 'mch.pregnancy.view', 'mch.immunisation.view', 'watch.dashboard.view', 'watch.signal.view'],
            'modules' => ['facility', 'quality', 'portal', 'vital', 'mch', 'watch'],
        ],
        [
            'text' => 'Facility and support',
            'module' => 'facility',
            'icon' => 'bi bi-buildings',
            'can' => ['facility.dashboard.view', 'facility.workorder.report', 'facility.housekeeping.view', 'facility.visitor.gate', 'facility.diet.view', 'facility.mortuary.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/facility', 'icon' => 'bi bi-speedometer2', 'can' => ['facility.dashboard.view', 'facility.workorder.report']],
                ['text' => 'Equipment', 'url' => '/facility/assets', 'icon' => 'bi bi-cpu', 'can' => ['facility.asset.view', 'facility.workorder.report']],
                ['text' => 'Work orders', 'url' => '/facility/work-orders', 'icon' => 'bi bi-wrench-adjustable', 'can' => 'facility.workorder.view'],
                ['text' => 'Cleaning', 'url' => '/facility/housekeeping', 'icon' => 'bi bi-droplet', 'can' => 'facility.housekeeping.view'],
                ['text' => 'Sterile services', 'url' => '/facility/sterile-services', 'icon' => 'bi bi-shield-check', 'can' => 'facility.cssd.view'],
                ['text' => 'Linen', 'url' => '/facility/linen', 'icon' => 'bi bi-basket', 'can' => 'facility.linen.view'],
                ['text' => 'Biomedical waste', 'url' => '/facility/waste', 'icon' => 'bi bi-trash3', 'can' => 'facility.waste.view'],
                ['text' => 'Diets and kitchen', 'url' => '/facility/diets', 'icon' => 'bi bi-egg-fried', 'can' => 'facility.diet.view'],
                ['text' => 'Ambulances', 'url' => '/facility/ambulances', 'icon' => 'bi bi-truck-front', 'can' => ['facility.ambulance.view', 'facility.ambulance.request']],
                ['text' => 'Mortuary', 'url' => '/facility/mortuary', 'icon' => 'bi bi-door-closed', 'can' => 'facility.mortuary.view'],
                ['text' => 'Visitors and attendants', 'url' => '/facility/visitors', 'icon' => 'bi bi-people', 'can' => 'facility.visitor.view'],
                ['text' => 'The gate', 'url' => '/facility/gate', 'icon' => 'bi bi-box-arrow-in-right', 'can' => 'facility.visitor.gate'],
            ],
        ],
        [
            'text' => 'Disease watch',
            'module' => 'watch',
            'icon' => 'bi bi-virus2',
            'can' => ['watch.dashboard.view', 'watch.signal.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/watch', 'icon' => 'bi bi-speedometer2', 'can' => 'watch.dashboard.view'],
                ['text' => 'Signals', 'url' => '/watch/signals', 'icon' => 'bi bi-bell', 'can' => 'watch.signal.view'],
                ['text' => 'Conditions watched', 'url' => '/watch/conditions', 'icon' => 'bi bi-list-check', 'can' => 'watch.signal.view'],
            ],
        ],
        [
            'text' => 'Mothers and babies',
            'module' => 'mch',
            'icon' => 'bi bi-heart',
            'can' => ['mch.dashboard.view', 'mch.pregnancy.view', 'mch.immunisation.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/mch', 'icon' => 'bi bi-speedometer2', 'can' => 'mch.dashboard.view'],
                ['text' => 'Pregnancies', 'url' => '/mch/pregnancies', 'icon' => 'bi bi-person-heart', 'can' => 'mch.pregnancy.view'],
                ['text' => 'Immunisation', 'url' => '/mch/immunisation', 'icon' => 'bi bi-shield-plus', 'can' => 'mch.immunisation.view'],
            ],
        ],
        [
            'text' => 'Births, deaths and police cases',
            'module' => 'vital',
            'icon' => 'bi bi-journal-medical',
            'can' => ['vital.dashboard.view', 'vital.birth.view', 'vital.death.view', 'vital.medicolegal.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/vital', 'icon' => 'bi bi-speedometer2', 'can' => 'vital.dashboard.view'],
                ['text' => 'Birth notifications', 'url' => '/vital/births', 'icon' => 'bi bi-balloon-heart', 'can' => 'vital.birth.view'],
                ['text' => 'Death certificates', 'url' => '/vital/deaths', 'icon' => 'bi bi-file-earmark-medical', 'can' => 'vital.death.view'],
                ['text' => 'Police cases', 'url' => '/vital/police-cases', 'icon' => 'bi bi-shield-exclamation', 'can' => 'vital.medicolegal.view'],
            ],
        ],
        [
            'text' => 'Quality and safety',
            'module' => 'quality',
            'icon' => 'bi bi-shield-plus',
            'can' => ['quality.incident.report', 'quality.dashboard.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/quality', 'icon' => 'bi bi-speedometer2', 'can' => ['quality.incident.report', 'quality.dashboard.view']],
                ['text' => 'Report an incident', 'url' => '/quality/incidents/new', 'icon' => 'bi bi-flag', 'can' => 'quality.incident.report'],
                ['text' => 'My reports', 'url' => '/quality/incidents/mine', 'icon' => 'bi bi-journal-text', 'can' => 'quality.incident.report'],
                ['text' => 'Incidents', 'url' => '/quality/incidents', 'icon' => 'bi bi-exclamation-triangle', 'can' => 'quality.incident.view'],
                ['text' => 'Actions', 'url' => '/quality/actions', 'icon' => 'bi bi-list-check', 'can' => ['quality.incident.report', 'quality.action.view']],
                ['text' => 'Infection control', 'url' => '/quality/infection', 'icon' => 'bi bi-virus', 'can' => 'quality.infection.view'],
                ['text' => 'Quality indicators', 'url' => '/quality/indicators', 'icon' => 'bi bi-graph-up', 'can' => 'quality.indicator.view'],
                ['text' => 'Clinical audits', 'url' => '/quality/audits', 'icon' => 'bi bi-clipboard-check', 'can' => 'quality.audit.view'],
                ['text' => 'Review of deaths', 'url' => '/quality/mortality', 'icon' => 'bi bi-heart-pulse', 'can' => 'quality.mortality.view'],
                ['text' => 'Documents', 'url' => '/quality/documents', 'icon' => 'bi bi-file-earmark-text', 'can' => 'quality.document.view'],
                ['text' => 'Documents to read', 'url' => '/quality/documents/to-read', 'icon' => 'bi bi-envelope-open', 'can' => 'quality.document.acknowledge'],
                ['text' => 'Clinical privileges', 'url' => '/quality/privileges', 'icon' => 'bi bi-person-badge', 'can' => 'quality.privilege.view'],
                ['text' => 'Licences and inspections', 'url' => '/quality/compliance', 'icon' => 'bi bi-calendar-check', 'can' => 'quality.compliance.view'],
                ['text' => 'Accreditation checklists', 'url' => '/quality/accreditation', 'icon' => 'bi bi-patch-check', 'can' => 'quality.accreditation.view'],
                ['text' => 'Committees and meetings', 'url' => '/quality/committees', 'icon' => 'bi bi-people', 'can' => 'quality.committee.view'],
                ['text' => 'Patient complaints', 'url' => '/quality/complaints', 'icon' => 'bi bi-chat-left-text', 'can' => 'quality.complaint.link'],
            ],
        ],
        [
            'text' => 'Patient relations',
            'module' => 'portal',
            'icon' => 'bi bi-heart',
            'can' => 'crm.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/crm', 'icon' => 'bi bi-speedometer2', 'can' => 'crm.dashboard.view'],
                ['text' => 'Contacts with patients', 'url' => '/crm/interactions', 'icon' => 'bi bi-telephone', 'can' => 'crm.interaction.view'],
                ['text' => 'Complaints', 'url' => '/crm/complaints', 'icon' => 'bi bi-exclamation-octagon', 'can' => 'crm.complaint.view'],
                ['text' => 'Visit ratings', 'url' => '/crm/feedback', 'icon' => 'bi bi-star', 'can' => 'crm.feedback.view'],
                ['text' => 'Patient groups', 'url' => '/crm/segments', 'icon' => 'bi bi-funnel', 'can' => 'crm.segment.view'],
                ['text' => 'Campaigns', 'url' => '/crm/campaigns', 'icon' => 'bi bi-megaphone', 'can' => 'crm.campaign.view'],
                ['text' => 'Patient forms', 'url' => '/crm/forms', 'icon' => 'bi bi-ui-checks', 'can' => 'crm.form.view'],
            ],
        ],
        [
            'text' => 'Patient portal',
            'module' => 'portal',
            'icon' => 'bi bi-phone',
            'can' => 'portal.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/patient-portal', 'icon' => 'bi bi-speedometer2', 'can' => 'portal.dashboard.view'],
                ['text' => 'Patient logins', 'url' => '/patient-portal/accounts', 'icon' => 'bi bi-person-badge', 'can' => 'portal.account.view'],
                ['text' => 'Results for patients', 'url' => '/patient-portal/releases', 'icon' => 'bi bi-eye', 'can' => 'portal.release.view'],
                ['text' => 'Refill requests', 'url' => '/patient-portal/refills', 'icon' => 'bi bi-capsule', 'can' => 'portal.refill.review'],
                ['text' => 'Patient messages', 'url' => '/patient-portal/messages', 'icon' => 'bi bi-chat-dots', 'can' => 'portal.message.view'],
                ['text' => 'Patient requests', 'url' => '/patient-portal/requests', 'icon' => 'bi bi-inbox', 'can' => 'portal.request.manage'],
                ['text' => 'Relatives acting for patients', 'url' => '/patient-portal/proxies', 'icon' => 'bi bi-people', 'can' => 'portal.proxy.review'],
                ['text' => 'Online payments', 'url' => '/patient-portal/payments', 'icon' => 'bi bi-credit-card', 'can' => 'portal.payment.view'],
                ['text' => 'Messages sent', 'url' => '/patient-portal/outbox', 'icon' => 'bi bi-send', 'can' => 'portal.communication.view'],
                ['text' => 'Who looked at what', 'url' => '/patient-portal/access-log', 'icon' => 'bi bi-journal-text', 'can' => 'portal.document.view'],
                ['text' => 'When results appear', 'url' => '/patient-portal/release-rules', 'icon' => 'bi bi-sliders', 'can' => 'portal.release.view'],
                ['text' => 'Portal settings', 'url' => '/patient-portal/settings', 'icon' => 'bi bi-gear', 'can' => 'portal.settings.manage'],
            ],
        ],
        [
            'header' => 'Insights and connections',
            'can' => ['analytics.dashboard.view', 'ai.use', 'ai.dashboard.view', 'ai.usecase.view', 'ai.incident.view', 'interoperability.dashboard.view'],
            'modules' => ['analytics', 'ai', 'interop'],
        ],
        [
            'text' => 'Reports and analytics',
            'module' => 'analytics',
            'icon' => 'bi bi-bar-chart-line',
            'can' => 'analytics.dashboard.view',
            'submenu' => [
                ['text' => 'Dashboards', 'url' => '/analytics', 'icon' => 'bi bi-speedometer2', 'can' => 'analytics.dashboard.view'],
                ['text' => 'KPIs and targets', 'url' => '/analytics/kpis', 'icon' => 'bi bi-speedometer', 'can' => 'analytics.kpi.view'],
                ['text' => 'Explore the figures', 'url' => '/analytics/explore', 'icon' => 'bi bi-search', 'can' => 'analytics.query.run'],
                ['text' => 'Ask a question', 'url' => '/analytics/ask', 'icon' => 'bi bi-chat-dots', 'can' => 'analytics.question.ask'],
                ['text' => 'Reports', 'url' => '/analytics/reports', 'icon' => 'bi bi-file-earmark-text', 'can' => 'analytics.report.view'],
                ['text' => 'Scheduled reports', 'url' => '/analytics/schedules', 'icon' => 'bi bi-envelope-paper', 'can' => 'analytics.schedule.manage'],
                ['text' => 'Planning forecasts', 'url' => '/analytics/forecast', 'icon' => 'bi bi-graph-up-arrow', 'can' => 'analytics.forecast.view'],
                ['text' => 'Compare with other hospitals', 'url' => '/analytics/benchmark', 'icon' => 'bi bi-people', 'can' => 'analytics.benchmark.view'],
                ['text' => 'National reporting (DGHS / DHIS2)', 'url' => '/analytics/national', 'icon' => 'bi bi-globe2', 'can' => 'analytics.national.view'],
                ['text' => 'Research datasets', 'url' => '/analytics/research', 'icon' => 'bi bi-journal-medical', 'can' => 'analytics.research.request'],
                ['text' => 'Data quality', 'url' => '/analytics/quality', 'icon' => 'bi bi-shield-check', 'can' => 'analytics.quality.view'],
                ['text' => 'Warehouse loads', 'url' => '/analytics/loads', 'icon' => 'bi bi-database', 'can' => 'analytics.load.view'],
            ],
        ],
        [
            'text' => 'AI assistant',
            'module' => 'ai',
            'icon' => 'bi bi-robot',
            'can' => ['ai.use', 'ai.dashboard.view', 'ai.usecase.view', 'ai.incident.view'],
            'submenu' => [
                ['text' => 'Overview', 'url' => '/ai', 'icon' => 'bi bi-speedometer2', 'can' => ['ai.use', 'ai.dashboard.view']],
                ['text' => 'Read a record back', 'url' => '/ai/summary', 'icon' => 'bi bi-person-lines-fill', 'can' => 'ai.use'],
                ['text' => 'Draft a visit note', 'url' => '/ai/notes', 'icon' => 'bi bi-journal-text', 'can' => 'ai.use'],
                ['text' => 'Ask about hospital policy', 'url' => '/ai/ask', 'icon' => 'bi bi-book', 'can' => 'ai.use'],
                ['text' => 'Ask about the figures', 'url' => '/ai/figures', 'icon' => 'bi bi-bar-chart-line', 'can' => 'ai.use'],
                ['text' => 'Forecast an item\'s use', 'url' => '/ai/forecast', 'icon' => 'bi bi-graph-up-arrow', 'can' => 'ai.use'],
                ['text' => 'AI drafts', 'url' => '/ai/outputs', 'icon' => 'bi bi-inbox', 'can' => ['ai.use', 'ai.output.view_all']],
                ['text' => 'AI functions', 'url' => '/ai/use-cases', 'icon' => 'bi bi-toggles', 'can' => 'ai.usecase.view'],
                ['text' => 'Providers and models', 'url' => '/ai/providers', 'icon' => 'bi bi-cpu', 'can' => ['ai.provider.manage', 'ai.model.manage']],
                ['text' => 'Prompts', 'url' => '/ai/prompts', 'icon' => 'bi bi-chat-square-text', 'can' => 'ai.prompt.manage'],
                ['text' => 'Knowledge for answers', 'url' => '/ai/knowledge', 'icon' => 'bi bi-journal-bookmark', 'can' => ['ai.knowledge.manage', 'ai.knowledge.approve']],
                ['text' => 'Evaluations', 'url' => '/ai/evaluations', 'icon' => 'bi bi-clipboard-check', 'can' => 'ai.eval.view'],
                ['text' => 'AI incidents', 'url' => '/ai/incidents', 'icon' => 'bi bi-exclamation-octagon', 'can' => ['ai.incident.view', 'ai.incident.manage']],
                ['text' => 'AI monitoring', 'url' => '/ai/monitoring', 'icon' => 'bi bi-activity', 'can' => 'ai.monitor.view'],
                ['text' => 'AI request log', 'url' => '/ai/audit', 'icon' => 'bi bi-journal-check', 'can' => 'ai.audit.view'],
            ],
        ],
        [
            'text' => 'Connections',
            'module' => 'interop',
            'icon' => 'bi bi-diagram-3',
            'can' => 'interoperability.dashboard.view',
            'submenu' => [
                ['text' => 'Overview', 'url' => '/interop', 'icon' => 'bi bi-speedometer2', 'can' => 'interoperability.dashboard.view'],
                ['text' => 'Partners', 'url' => '/interop/partners', 'icon' => 'bi bi-building', 'can' => 'interoperability.partner.view'],
                ['text' => 'Messages between systems', 'url' => '/interop/messages', 'icon' => 'bi bi-arrow-left-right', 'can' => 'interoperability.message.view'],
                ['text' => 'Results from other laboratories', 'url' => '/interop/results', 'icon' => 'bi bi-clipboard2-pulse', 'can' => 'interoperability.hl7.review'],
                ['text' => 'Who was shown or sent what', 'url' => '/interop/access-log', 'icon' => 'bi bi-shield-check', 'can' => 'interoperability.exchange.log.view'],
                ['text' => 'Scanner worklist', 'url' => '/interop/worklist', 'icon' => 'bi bi-card-list', 'can' => 'interoperability.dicom.worklist.view'],
                ['text' => 'National exchange (DGHS)', 'url' => '/interop/national', 'icon' => 'bi bi-globe2', 'can' => 'interoperability.national.publish'],
                ['text' => 'Numbers other systems use', 'url' => '/interop/id-maps', 'icon' => 'bi bi-link-45deg', 'can' => 'interoperability.idmap.view'],
                ['text' => 'Codes other systems understand', 'url' => '/interop/terminology', 'icon' => 'bi bi-book', 'can' => 'interoperability.terminology.view'],
                ['text' => 'Integration settings', 'url' => '/interop/settings', 'icon' => 'bi bi-gear', 'can' => 'interoperability.settings.manage'],
            ],
        ],
        [
            'header' => 'Administration',
            'can' => ['settings.view', 'system.health.view'],
        ],
        [
            'text' => 'Hospital set-up',
            'icon' => 'bi bi-hospital',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Hospital & Branches',
                    'url' => '/organizations',
                    'icon' => 'bi bi-building',
                    'can' => 'manage organizations',
                ],
                [
                    'text' => 'Branding, e-mail and contact',
                    'url' => '/settings/system',
                    'icon' => 'bi bi-palette',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Departments',
                    'url' => '/departments',
                    'icon' => 'bi bi-diagram-3',
                    'can' => 'manage departments',
                ],
            ],
        ],
        [
            'text' => 'Master data',
            'icon' => 'bi bi-database',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Countries',
                    'url' => '/master-data/countries',
                    'icon' => 'bi bi-globe',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Geography (divisions to unions)',
                    'url' => '/master-data/geography',
                    'icon' => 'bi bi-pin-map',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'States',
                    'url' => '/master-data/states',
                    'icon' => 'bi bi-map',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Currencies',
                    'url' => '/master-data/currencies',
                    'icon' => 'bi bi-currency-dollar',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Identification Types',
                    'url' => '/master-data/identification-types',
                    'icon' => 'bi bi-card-list',
                    'can' => 'settings.view',
                ],
            ],
        ],
        [
            'text' => 'Administration',
            'icon' => 'bi bi-gear',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Data import',
                    'url' => '/imports',
                    'icon' => 'bi bi-upload',
                    'can' => 'import.view',
                ],
                [
                    'text' => 'Users',
                    'url' => '/users',
                    'icon' => 'bi bi-people',
                    'can' => 'manage users',
                ],
                [
                    'text' => 'Roles',
                    'url' => '/roles',
                    'icon' => 'bi bi-shield-lock',
                    'can' => 'manage roles',
                ],
                [
                    'text' => 'Permissions',
                    'url' => '/permissions',
                    'icon' => 'bi bi-key',
                    'can' => 'manage permissions',
                ],
                [
                    'text' => 'Workflows',
                    'url' => '/workflow',
                    'icon' => 'bi bi-diagram-2',
                    'can' => 'workflow.view',
                ],
            ],
        ],
        [
            'text' => 'Security and audit',
            'icon' => 'bi bi-shield-lock',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Audit Logs',
                    'url' => '/audit-logs',
                    'icon' => 'bi bi-journal-text',
                    'can' => 'audit.view',
                ],
                [
                    'text' => 'Activity Logs',
                    'url' => '/activity-logs',
                    'icon' => 'bi bi-activity',
                    'can' => 'activity.view',
                ],
                [
                    'text' => 'Security Events',
                    'url' => '/security-events',
                    'icon' => 'bi bi-shield-exclamation',
                    'can' => 'security.event.view',
                ],
                [
                    'text' => 'Login History',
                    'url' => '/login-history',
                    'icon' => 'bi bi-box-arrow-in-right',
                    'can' => 'login.history.view',
                ],
                [
                    'text' => 'Change Password',
                    'url' => '/change-password',
                    'icon' => 'bi bi-lock',
                ],
            ],
        ],
        [
            'text' => 'System',
            'icon' => 'bi bi-hdd-stack',
            'can' => 'system.health.view',
            'submenu' => [
                [
                    'text' => 'System Health',
                    'url' => '/system/health',
                    'icon' => 'bi bi-heart-pulse',
                    'can' => 'system.health.view',
                ],
                [
                    'text' => 'Queue Monitor',
                    'url' => '/system/queue',
                    'icon' => 'bi bi-stack',
                    'can' => 'system.queue.view',
                ],
                [
                    'text' => 'Scheduled Jobs',
                    'url' => '/system/scheduled-jobs',
                    'icon' => 'bi bi-calendar-week',
                    'can' => 'system.scheduler.view',
                ],
                [
                    'text' => 'About',
                    'url' => '/system/about',
                    'icon' => 'bi bi-info-circle',
                    'can' => 'system.health.view',
                ],
            ],
        ],
        [
            'header' => 'Help',
        ],
        [
            'text' => 'My display settings',
            'url' => '/preferences',
            'icon' => 'bi bi-sliders',
        ],
        [
            'text' => 'Help and support',
            'icon' => 'bi bi-life-preserver',
            'submenu' => [
                [
                    'text' => 'Quick-start cards',
                    'url' => '/help/quick-start',
                    'icon' => 'bi bi-question-circle',
                ],
                [
                    'text' => 'Downtime kit',
                    'url' => '/downtime',
                    'icon' => 'bi bi-printer',
                    'can' => 'downtime.view',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/menu.html
    |
    */

    'filters' => [
        ModuleMenuFilter::class,
        GateFilter::class,
        HrefFilter::class,
        SearchFilter::class,
        ActiveFilter::class,
        ClassesFilter::class,
        LangFilter::class,
        DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/plugins.html
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/2.1.8/js/dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css',
                ],
            ],
        ],
        // The Datatables 'Buttons' extension, required by the 'with-buttons'
        // attribute of the datatable component. JSZip powers the excel export
        // and pdfmake the pdf one, drop them when you don't need those.

        'DatatablesButtons' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/4.0.2/js/dataTables.buttons.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.bootstrap5.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.html5.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/4.0.2/js/buttons.print.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.3.3/pdfmake.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.3.3/vfs_fonts.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/4.0.2/css/buttons.bootstrap5.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css',
                ],

                // The AdminLTE v4 compatibility theme for Select2. Replace it
                // by the 'adminlte-select2.rtl.min.css' file on RTL mode.

                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-select2.min.css',
                    'rtl' => '//cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-select2.rtl.min.css',
                ],
            ],
        ],
        // Every dropdown in the product is searchable (owner instruction 2026-11-02). Tom Select is copied into public/vendor/tom-select so it works without internet; public/js/searchable-select.js
        // enhances every <select> (opt out with data-plain; load options from the server with data-remote). Loaded on every AdminLTE page, including sign-in. The patient portal has its own layout and loads the same files.
        'TomSelect' => [
            'active' => true,
            'files' => [
                ['type' => 'css', 'asset' => true, 'location' => 'vendor/tom-select/tom-select.bootstrap5.min.css'],
                ['type' => 'css', 'asset' => true, 'location' => 'css/searchable-select.css'],
                ['type' => 'js', 'asset' => true, 'location' => 'vendor/tom-select/tom-select.complete.min.js'],
                ['type' => 'js', 'asset' => true, 'location' => 'js/searchable-select.js'],
            ],
        ],
        'Tabulator' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/js/tabulator.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/css/tabulator_bootstrap5.min.css',
                ],
            ],
        ],
        'Flatpickr' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
                ],
            ],
        ],
        'Quill' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css',
                ],
            ],
        ],
        'NoUiSlider' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/nouislider@15.8.1/dist/nouislider.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/nouislider@15.8.1/dist/nouislider.min.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@11',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | The 'default_tab' options add a tab that is always open, the 'buttons'
    | ones pick the controls of the tab bar, and the 'options' ones tune the
    | behavior: 'loading_screen' is the duration in milliseconds of the
    | loading overlay of a tab (zero disables it), 'auto_show_new_tab' focuses
    | a tab as soon as it is created and 'use_navbar_items' also turns the
    | links of the top navbar into tabs.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/iframe_mode.html
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support. When enabled, the Livewire
    | styles and scripts are added to the layout.
    |
    | For detailed instructions you can look the livewire here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'livewire' => false,

    /*
    |--------------------------------------------------------------------------
    | Single Page Navigation
    |--------------------------------------------------------------------------
    |
    | AdminLTE re-initializes its plugins on the 'turbo:load' event of Turbo
    | Drive, but it knows nothing about Livewire. So, after a 'wire:navigate'
    | visit the sidebar, the treeview and the card tools would stay dead.
    |
    | With this option enabled, the package bridges the Livewire navigation
    | event to the AdminLTE lifecycle. Disable it when your application takes
    | care of that on its own.
    |
    */

    'spa_navigation' => true,
];
