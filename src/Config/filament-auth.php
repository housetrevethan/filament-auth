<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microsoft OAuth
    |--------------------------------------------------------------------------
    | our_tenant_id  — House Trevethan's Entra tenant. Users from this tenant
    |                  are provisioned with the Core role.
    | allowed_tenant_ids — Client tenant UUIDs. Users from these tenants are
    |                      provisioned with the Client role. Add via .env as a
    |                      comma-separated list.
    */
    'microsoft' => [
        'house_trevethan_tenant_id'      => env('HOUSE_TREVETHAN_TENANT_ID', ''),
        'allowed_tenant_ids' => array_filter(
            explode(',', env('MICROSOFT_ALLOWED_TENANT_IDS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'mfa' => [
        'app_enabled'           => env('MFA_APP_ENABLED', true),
        'email_enabled'         => env('MFA_EMAIL_ENABLED', true),
        'recovery_code_count'   => env('MFA_RECOVERY_CODE_COUNT', 8),
        'code_expiry_minutes'   => env('MFA_CODE_EXPIRY_MINUTES', 5),
    ],
];
