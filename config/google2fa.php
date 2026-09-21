<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google2FA
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Google2FA package.
    |
    */

    'enabled' => env('MFA_ENABLED', true),

    'window' => env('MFA_WINDOW', 4),

    'forbidden' => env('MFA_FORBIDDEN', false),

    'lifetime' => env('MFA_LIFETIME', 0),

    'keep_alive' => env('MFA_KEEP_ALIVE', false),

    'session_var' => env('MFA_SESSION_VAR', 'google2fa'),

    'otp_input' => env('MFA_OTP_INPUT', 'one_time_password'),

    'qrcode_width' => env('MFA_QRCODE_WIDTH', 200),

    'qrcode_height' => env('MFA_QRCODE_HEIGHT', 200),

    'qrcode_background' => env('MFA_QRCODE_BACKGROUND', '#FFFFFF'),

    'qrcode_foreground' => env('MFA_QRCODE_FOREGROUND', '#000000'),

    'qr_code_background' => env('MFA_QRCODE_BACKGROUND', '#FFFFFF'),

    'qr_code_foreground' => env('MFA_QRCODE_FOREGROUND', '#000000'),

    'recovery_codes' => [
        'enabled' => env('MFA_RECOVERY_CODES_ENABLED', true),
        'count' => env('MFA_RECOVERY_CODES_COUNT', 10),
        'length' => env('MFA_RECOVERY_CODES_LENGTH', 16),
    ],
];
