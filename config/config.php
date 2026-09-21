<?php

return [
    'app' => [
        'name' => 'صندوق عائلي',
        'timezone' => 'Asia/Riyadh',
        'locale' => 'ar',
        'debug' => true,
        'base_path' => '', // set automatically in bootstrap if empty
        'otp_stub' => true, // simulate OTP instead of a real SMS gateway (code is echoed on screen for LOCAL requests only). Set to false in production.
        'otp_length' => 4, // per the feature list (FLD): 4 digits. Raise to 6 for stronger protection; views follow this value.
        'otp_expiry_minutes' => 10,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'sandouk_db',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'sandouk_session',
        'lifetime' => 60 * 60 * 8,
    ],
];
