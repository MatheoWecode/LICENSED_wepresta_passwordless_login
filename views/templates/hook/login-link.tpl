{**
 * Passwordless Login - Login link for navigation hooks
 *
 * Variables:
 * - $pl_auth_url: URL to the passwordless login page
 *}

<a href="{$pl_auth_url|escape:'htmlall':'UTF-8'}" class="pl-login-link" rel="nofollow" title="{l s='Sign in' d='Modules.Weprestapasswordlesslogin.Front'}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span>{l s='Sign in' d='Modules.Weprestapasswordlesslogin.Front'}</span>
</a>
