<?php

return [
    // Any staff screen can be taken as Excel or PDF with ?export=xlsx|pdf. These limits keep one click from loading the whole database.
    'max_pages' => (int) env('EXPORT_MAX_PAGES', 40),     // pages of a paged list that are followed
    'max_rows' => (int) env('EXPORT_MAX_ROWS', 20000),    // rows over all tables
    'per_minute' => (int) env('EXPORT_PER_MINUTE', 20),   // per person

    // Screens that show secrets or sign-in material are never exported (route name patterns).
    'except' => ['admin.*mfa*', 'admin.*token*', 'admin.*secret*', 'admin.*password*', 'admin.security.*'],

    // A PDF with more columns than this is printed on a landscape page.
    'landscape_columns' => 7,
];
