<?php
/**
 * Auth Configuration
 * mode: 'plugin' (shared table with AuthMe/nLogin/etc.) or 'standalone' (web-only users table)
 */
return [
    // 'plugin' or 'standalone'
    'mode' => 'plugin',

    // Plugin mode settings — configurable table/column mapping
    'plugin' => [
        'table'   => 'authme',
        'columns' => [
            'id'            => 'id',
            'username'      => 'realname',
            'password'      => 'password',
            'ip'            => 'ip',
            'last_login'    => 'lastlogin',
            'register_date' => 'regdate',
            'email'         => 'email',
        ],
        // Hash algorithm: 'SHA256', 'BCRYPT', 'ARGON2', 'PBKDF2'
        'hash_algorithm' => 'SHA256',
    ],

    // Standalone mode settings
    'standalone' => [
        'hash_algorithm' => 'BCRYPT',
    ],
];
