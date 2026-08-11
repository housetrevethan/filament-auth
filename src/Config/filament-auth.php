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
        'house_trevethan_tenant_id' => env('HOUSE_TREVETHAN_TENANT_ID', ''),
        'allowed_tenant_ids' => array_filter(
            explode(',', env('MICROSOFT_ALLOWED_TENANT_IDS', ''))
        ),
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => 'common',
        'include_tenant_info' => true,
        'include_avatar' => true,
        'include_avatar_size' => '648x648',
    ],

    'filament-routes' => [
        'filament-dashboard-route' => env('FILAMENT_DASHBOARD_ROUTE'),
        'filament-login-route' => env('FILAMENT_LOGIN_ROUTE'),
        'failed-login-redirect' => env('FAILED_LOGIN_REDIRECT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model & Policies
    |--------------------------------------------------------------------------
    | The application's user model, and the policy registered against it.
    | Override the policy to replace this package's authorization rules
    | wholesale — though adjusting roles below is usually enough.
    */
    'user_model' => env('FILAMENT_AUTH_USER_MODEL', 'App\Models\User'),

    'policies' => [
        'user' => Housetrevethan\FilamentAuth\Policies\UserPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    | Roles and permissions are stored via spatie/laravel-permission, so they
    | can be created and edited at runtime. The definitions below are the
    | code-defined defaults, synced into the database by:
    |
    |     php artisan filament-auth:sync-roles
    |
    | Applications may add their own roles here, or create them at runtime.
    | Either way they can be granted the package permissions listed in
    | Housetrevethan\FilamentAuth\Enums\Permission.
    |
    | super      — bypasses all authorization checks (Gate::before).
    | default    — assigned to new users when no role is specified.
    | protected  — reserved roles. Assigning one requires the
    |              roles.assignProtected permission, which stops an
    |              administrator from escalating themselves or others.
    */
    'roles' => [
        'super' => env('FILAMENT_AUTH_SUPER_ROLE', 'house_trevethan_staff'),

        'default' => env('FILAMENT_AUTH_DEFAULT_ROLE', 'client'),

        'protected' => [
            'house_trevethan_staff',
        ],

        /*
        | 'permissions' accepts an array of permission keys, or the string '*'
        | to grant every registered permission.
        */
        'definitions' => [
            'house_trevethan_staff' => [
                'label' => 'House Trevethan Staff',
                'permissions' => '*',
            ],

            'admin' => [
                'label' => 'Admin',
                'permissions' => [
                    'panel.access',
                    'users.viewAny',
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',
                    'users.restore',
                    'users.forceDelete',
                    'users.editProfile',
                    'users.changeRole',
                ],
            ],

            'client' => [
                'label' => 'Client',
                'permissions' => [],
            ],
        ],

        /*
        | Role granted to users provisioned through Microsoft OAuth, based on
        | the tenant their identity came from.
        */
        'oauth' => [
            'house_trevethan_tenant' => 'house_trevethan_staff',
            'allowed_tenant' => 'admin',
            'fallback' => 'client',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'mfa' => [
        'app_enabled' => env('MFA_APP_ENABLED', true),
        'email_enabled' => env('MFA_EMAIL_ENABLED', true),
        'recovery_code_count' => env('MFA_RECOVERY_CODE_COUNT', 8),
        'code_expiry_minutes' => env('MFA_CODE_EXPIRY_MINUTES', 5),
    ],
];
