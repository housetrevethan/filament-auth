<?php

namespace Housetrevethan\FilamentAuth\Enums;

enum OAuthRejectionReason: string
{
    /**
     * The provider returned no tenant id at all, so the identity cannot be
     * attributed to an organisation and must not be trusted.
     */
    case MissingTenant = 'missing_tenant';

    /**
     * The identity presented a tenant that is not in the configured
     * allowlist. The tenant is well formed, it simply is not permitted
     * to authenticate against this application.
     */
    case TenantNotAllowed = 'tenant_not_allowed';

    /**
     * The email address belongs to an account that was created locally
     * and has never been linked to an OAuth provider.
     */
    case LocalAccount = 'local_account';

    /**
     * The email address is already bound to a different OAuth identity.
     * Auto-linking here would allow one identity to take over another
     * account, so the login is refused.
     */
    case EmailConflict = 'email_conflict';

    /**
     * The identity is known, but is presenting a different tenant than the
     * one it was provisioned under. Moving an account between tenants must
     * be done deliberately, not silently at login.
     */
    case TenantMismatch = 'tenant_mismatch';
}
