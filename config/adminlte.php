<?php

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
        [
            'type' => 'sidebar-menu-search',
            'text' => 'Search menu...',
        ],
        // Navbar items (right side) - custom dropdowns handled via content_top_nav_right
        [
            'type' => 'darkmode-widget',
            'topnav_right' => true,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        // Sidebar items:
        [
            'text' => 'Dashboard',
            'url' => '/dashboard',
            'icon' => 'bi bi-speedometer2',
        ],
        [
            'text' => 'Hospital Operations',
            'icon' => 'bi bi-hospital',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Hospital Dashboard',
                    'url' => '#',
                    'icon' => 'bi bi-speedometer',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Hospital & Branches',
                    'url' => '/admin/companies',
                    'icon' => 'bi bi-building',
                    'can' => 'manage companies',
                ],
                [
                    'text' => 'Departments',
                    'url' => '/admin/departments',
                    'icon' => 'bi bi-diagram-3',
                    'can' => 'manage departments',
                ],
            ],
        ],
        [
            'text' => 'Patients',
            'icon' => 'bi bi-person',
            'can' => 'patients.view',
            'submenu' => [
                [
                    'text' => 'All Patients',
                    'url' => '/admin/patients',
                    'icon' => 'bi bi-people',
                    'can' => 'patients.view',
                ],
                [
                    'text' => 'Register Patient',
                    'url' => '/admin/patients/create',
                    'icon' => 'bi bi-person-plus',
                    'can' => 'patients.create',
                ],
                [
                    'text' => 'Search Patients',
                    'url' => '/admin/patients/search',
                    'icon' => 'bi bi-search',
                    'can' => 'patients.view',
                ],
                [
                    'text' => 'Duplicate Review Queue',
                    'url' => '/admin/patients/duplicates',
                    'icon' => 'bi bi-people-fill',
                    'can' => 'patients.merge',
                ],
                [
                    'text' => 'Pending Amendments',
                    'url' => '/admin/patients/amendments',
                    'icon' => 'bi bi-pencil-square',
                    'can' => 'patients.amend.approve',
                ],
            ],
        ],
        [
            'text' => 'Appointments',
            'icon' => 'bi bi-calendar-check',
            'can' => 'appointments.view',
            'submenu' => [
                [
                    'text' => 'All Appointments',
                    'url' => '/admin/appointments',
                    'icon' => 'bi bi-calendar-check',
                    'can' => 'appointments.view',
                ],
                [
                    'text' => 'New Appointment',
                    'url' => '/admin/appointments/create',
                    'icon' => 'bi bi-calendar-plus',
                    'can' => 'appointments.create',
                ],
                [
                    'text' => 'Calendar',
                    'url' => '/admin/appointments-calendar',
                    'icon' => 'bi bi-calendar3',
                    'can' => 'appointments.view',
                ],
                [
                    'text' => 'Waiting Queue',
                    'url' => '/admin/queue',
                    'icon' => 'bi bi-ticket',
                    'can' => 'appointments.queue',
                ],
                [
                    'text' => 'Doctor Schedules',
                    'url' => '/admin/schedules',
                    'icon' => 'bi bi-clock',
                    'can' => 'appointments.manage_schedule',
                ],
                [
                    'text' => 'Providers',
                    'url' => '/admin/providers',
                    'icon' => 'bi bi-person-badge',
                    'can' => 'appointments.manage_provider',
                ],
                [
                    'text' => 'Provider Unavailability / Blocking',
                    'url' => '/admin/provider-unavailability',
                    'icon' => 'bi bi-calendar-x',
                    'can' => 'appointments.manage_block',
                ],
                [
                    'text' => 'Holidays',
                    'url' => '/admin/holidays',
                    'icon' => 'bi bi-calendar-event',
                    'can' => 'appointments.manage_holiday',
                ],
                [
                    'text' => 'Appointment Types',
                    'url' => '/admin/appointment-types',
                    'icon' => 'bi bi-tags',
                    'can' => 'appointments.manage_schedule',
                ],
                [
                    'text' => 'Rooms',
                    'url' => '/admin/appointment-rooms',
                    'icon' => 'bi bi-door-open',
                    'can' => 'appointments.manage_schedule',
                ],
            ],
        ],
        [
            'text' => 'Clinical',
            'icon' => 'bi bi-heart-pulse',
            'can' => 'encounters.view',
            'submenu' => [
                [
                    'text' => 'Doctor Queue',
                    'url' => '/admin/queue',
                    'icon' => 'bi bi-ticket',
                    'can' => 'queue.view',
                ],
                [
                    'text' => 'My Encounters',
                    'url' => '/admin/encounters',
                    'icon' => 'bi bi-clipboard-data',
                    'can' => 'encounters.view',
                ],
                [
                    'text' => 'New Encounter',
                    'url' => '/admin/encounters/create',
                    'icon' => 'bi bi-plus-circle',
                    'can' => 'encounters.create',
                ],
                [
                    'text' => 'Clinical Reports',
                    'url' => '/admin/reports/clinical',
                    'icon' => 'bi bi-bar-chart',
                    'can' => 'encounter.export',
                ],
            ],
        ],
        [
            'text' => 'Billing & Revenue',
            'icon' => 'bi bi-cash-coin',
            'can' => 'billing.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/admin/billing',
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
                            'url' => '/admin/billing/invoices/create',
                            'icon' => 'bi bi-plus-circle',
                            'can' => 'billing.invoice.create',
                        ],
                        [
                            'text' => 'Invoices',
                            'url' => '/admin/billing/invoices',
                            'icon' => 'bi bi-receipt',
                            'can' => 'billing.invoice.view',
                        ],
                        [
                            'text' => 'Charges',
                            'url' => '/admin/billing/charges',
                            'icon' => 'bi bi-list-check',
                            'can' => 'billing.charge.view',
                        ],
                        [
                            'text' => 'Adjustments',
                            'url' => '/admin/billing/adjustments',
                            'icon' => 'bi bi-sliders',
                            'can' => 'billing.adjustment.request',
                        ],
                        [
                            'text' => 'Outstanding',
                            'url' => '/admin/billing/reports/receivables',
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
                            'url' => '/admin/billing/payments/create',
                            'icon' => 'bi bi-cash',
                            'can' => 'billing.payment.create',
                        ],
                        [
                            'text' => 'Payments',
                            'url' => '/admin/billing/payments',
                            'icon' => 'bi bi-credit-card',
                            'can' => 'billing.payment.view',
                        ],
                        [
                            'text' => 'Receipts',
                            'url' => '/admin/billing/receipts',
                            'icon' => 'bi bi-file-earmark-text',
                            'can' => 'billing.receipt.view',
                        ],
                        [
                            'text' => 'Refunds',
                            'url' => '/admin/billing/refunds',
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
                            'url' => '/admin/billing/cashier/my-session',
                            'icon' => 'bi bi-person-badge',
                            'can' => 'billing.cashier.view',
                        ],
                        [
                            'text' => 'Open Session',
                            'url' => '/admin/billing/cashier/open',
                            'icon' => 'bi bi-door-open',
                            'can' => 'billing.cashier.open',
                        ],
                        [
                            'text' => 'All Sessions',
                            'url' => '/admin/billing/cashier',
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
                            'url' => '/admin/billing/items',
                            'icon' => 'bi bi-list-ul',
                            'can' => 'billing.pricing.view',
                        ],
                        [
                            'text' => 'Categories',
                            'url' => '/admin/billing/categories',
                            'icon' => 'bi bi-diagram-3',
                            'can' => 'billing.pricing.view',
                        ],
                        [
                            'text' => 'Price Lists',
                            'url' => '/admin/billing/price-lists',
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
                            'text' => 'Companies',
                            'url' => '/admin/billing/corporates',
                            'icon' => 'bi bi-building',
                            'can' => 'billing.corporate.view',
                        ],
                        [
                            'text' => 'Receivables',
                            'url' => '/admin/billing/corporates/receivables',
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
                            'url' => '/admin/billing/insurance/providers',
                            'icon' => 'bi bi-shield-check',
                            'can' => 'insurance.view',
                        ],
                        [
                            'text' => 'Policies',
                            'url' => '/admin/billing/insurance/policies',
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
                            'url' => '/admin/billing/reports/billing',
                            'icon' => 'bi bi-file-bar-graph',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Collection Report',
                            'url' => '/admin/billing/reports/collection',
                            'icon' => 'bi bi-cash-coin',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Revenue Report',
                            'url' => '/admin/billing/reports/revenue',
                            'icon' => 'bi bi-graph-up',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Receivables Report',
                            'url' => '/admin/billing/reports/receivables',
                            'icon' => 'bi bi-hourglass-split',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Refunds Report',
                            'url' => '/admin/billing/reports/refunds',
                            'icon' => 'bi bi-arrow-counterclockwise',
                            'can' => 'billing.report.view',
                        ],
                        [
                            'text' => 'Discounts Report',
                            'url' => '/admin/billing/reports/discounts',
                            'icon' => 'bi bi-percent',
                            'can' => 'billing.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Settings',
                    'url' => '/admin/billing/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'billing.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Laboratory',
            'icon' => 'bi bi-clipboard2-pulse',
            'can' => 'lab.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/admin/lab',
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
                            'url' => '/admin/lab/orders',
                            'icon' => 'bi bi-clipboard-check',
                            'can' => 'lab.order.view',
                        ],
                        [
                            'text' => 'Worklist',
                            'url' => '/admin/lab/worklist',
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
                            'url' => '/admin/lab/specimens',
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
                            'url' => '/admin/lab/critical-results',
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
                            'url' => '/admin/lab/reports',
                            'icon' => 'bi bi-file-earmark-medical',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Daily Volume',
                            'url' => '/admin/lab/reports-analytics/daily-volume',
                            'icon' => 'bi bi-bar-chart',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Sample Rejection',
                            'url' => '/admin/lab/reports-analytics/sample-rejection',
                            'icon' => 'bi bi-x-circle',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Critical Result Report',
                            'url' => '/admin/lab/reports-analytics/critical-results',
                            'icon' => 'bi bi-exclamation-triangle',
                            'can' => 'lab.report.view',
                        ],
                        [
                            'text' => 'Amendments Report',
                            'url' => '/admin/lab/reports-analytics/result-amendments',
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
                            'url' => '/admin/lab/tests',
                            'icon' => 'bi bi-journal-medical',
                            'can' => 'lab.test.view',
                        ],
                        [
                            'text' => 'Panels',
                            'url' => '/admin/lab/panels',
                            'icon' => 'bi bi-collection',
                            'can' => 'lab.test.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Quality Control',
                    'url' => '/admin/lab/qc',
                    'icon' => 'bi bi-patch-check',
                    'can' => 'lab.qc.view',
                ],
                [
                    'text' => 'Analyzers',
                    'url' => '/admin/lab/analyzers',
                    'icon' => 'bi bi-hdd-network',
                    'can' => 'lab.analyzer.view',
                ],
                [
                    'text' => 'Settings',
                    'url' => '/admin/lab/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'lab.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Radiology',
            'icon' => 'bi bi-radioactive',
            'can' => 'radiology.dashboard.view',
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url' => '/admin/radiology',
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
                            'url' => '/admin/radiology/orders',
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
                            'url' => '/admin/radiology/examinations',
                            'icon' => 'bi bi-calendar-week',
                            'can' => 'radiology.examination.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Studies',
                    'url' => '/admin/radiology/studies',
                    'icon' => 'bi bi-images',
                    'can' => 'radiology.study.view',
                ],
                [
                    'text' => 'Radiologist Worklist',
                    'url' => '/admin/radiology/worklist',
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
                            'url' => '/admin/radiology/reports',
                            'icon' => 'bi bi-file-earmark-medical',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Daily Volume',
                            'url' => '/admin/radiology/analytics/daily-volume',
                            'icon' => 'bi bi-bar-chart',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Modality Utilization',
                            'url' => '/admin/radiology/analytics/modality-utilization',
                            'icon' => 'bi bi-pie-chart',
                            'can' => 'radiology.report.view',
                        ],
                        [
                            'text' => 'Turnaround Time',
                            'url' => '/admin/radiology/analytics/turnaround-time',
                            'icon' => 'bi bi-stopwatch',
                            'can' => 'radiology.report.view',
                        ],
                    ],
                ],
                [
                    'text' => 'Critical Findings',
                    'url' => '/admin/radiology/critical-findings',
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
                            'url' => '/admin/radiology/procedures',
                            'icon' => 'bi bi-journal-medical',
                            'can' => 'radiology.procedure.view',
                        ],
                        [
                            'text' => 'Modalities',
                            'url' => '/admin/radiology/modalities',
                            'icon' => 'bi bi-cpu',
                            'can' => 'radiology.modality.view',
                        ],
                    ],
                ],
                [
                    'text' => 'PACS',
                    'url' => '/admin/radiology/pacs',
                    'icon' => 'bi bi-hdd-network',
                    'can' => 'radiology.pacs.view',
                ],
                [
                    'text' => 'Settings',
                    'url' => '/admin/radiology/settings',
                    'icon' => 'bi bi-gear',
                    'can' => 'radiology.settings.manage',
                ],
            ],
        ],
        [
            'text' => 'Master Data',
            'icon' => 'bi bi-database',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Countries',
                    'url' => '/admin/master-data/countries',
                    'icon' => 'bi bi-globe',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'States',
                    'url' => '/admin/master-data/states',
                    'icon' => 'bi bi-map',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Currencies',
                    'url' => '/admin/master-data/currencies',
                    'icon' => 'bi bi-currency-dollar',
                    'can' => 'settings.view',
                ],
                [
                    'text' => 'Identification Types',
                    'url' => '/admin/master-data/identification-types',
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
                    'text' => 'Users',
                    'url' => '/admin/users',
                    'icon' => 'bi bi-people',
                    'can' => 'manage users',
                ],
                [
                    'text' => 'Roles',
                    'url' => '/admin/roles',
                    'icon' => 'bi bi-shield-lock',
                    'can' => 'manage roles',
                ],
                [
                    'text' => 'Permissions',
                    'url' => '/admin/permissions',
                    'icon' => 'bi bi-key',
                    'can' => 'manage permissions',
                ],
                [
                    'text' => 'Workflows',
                    'url' => '/admin/workflow',
                    'icon' => 'bi bi-diagram-2',
                    'can' => 'workflow.view',
                ],
            ],
        ],
        [
            'text' => 'Security & Audit',
            'icon' => 'bi bi-shield-lock',
            'can' => 'settings.view',
            'submenu' => [
                [
                    'text' => 'Audit Logs',
                    'url' => '/admin/audit-logs',
                    'icon' => 'bi bi-journal-text',
                    'can' => 'audit.view',
                ],
                [
                    'text' => 'Activity Logs',
                    'url' => '/admin/activity-logs',
                    'icon' => 'bi bi-activity',
                    'can' => 'activity.view',
                ],
                [
                    'text' => 'Security Events',
                    'url' => '/admin/security-events',
                    'icon' => 'bi bi-shield-exclamation',
                    'can' => 'security.event.view',
                ],
                [
                    'text' => 'Login History',
                    'url' => '/admin/login-history',
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
                    'url' => '/admin/system/health',
                    'icon' => 'bi bi-heart-pulse',
                    'can' => 'system.health.view',
                ],
                [
                    'text' => 'Queue Monitor',
                    'url' => '/admin/system/queue',
                    'icon' => 'bi bi-stack',
                    'can' => 'system.queue.view',
                ],
                [
                    'text' => 'Scheduled Jobs',
                    'url' => '/admin/system/scheduled-jobs',
                    'icon' => 'bi bi-calendar-week',
                    'can' => 'system.scheduler.view',
                ],
                [
                    'text' => 'About',
                    'url' => '/admin/system/about',
                    'icon' => 'bi bi-info-circle',
                    'can' => 'system.health.view',
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
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
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
        'TomSelect' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css',
                ],
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
