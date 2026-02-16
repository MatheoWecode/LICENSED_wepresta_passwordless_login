{**
 * Passwordless Login - Auth app container (3 steps)
 *
 * Variables:
 * - $pl_api_url: AJAX endpoint URL
 * - $pl_csrf_token: CSRF token
 * - $pl_google_enabled: bool
 * - $pl_google_client_id: string
 * - $pl_show_classic_login: bool
 * - $pl_show_logo: bool
 * - $pl_shop_name: string
 * - $pl_shop_logo_url: string
 * - $pl_resend_countdown: int
 * - $pl_back_url: string
 * - $pl_from_checkout: bool
 * - $pl_login_url: string (native PS login page)
 *}

<div id="passwordless-login"
     class="pl-auth"
     data-api-url="{$pl_api_url|escape:'htmlall':'UTF-8'}"
     data-csrf-token="{$pl_csrf_token|escape:'htmlall':'UTF-8'}"
     data-google-enabled="{$pl_google_enabled|intval}"
     data-google-client-id="{$pl_google_client_id|escape:'htmlall':'UTF-8'}"
     data-resend-countdown="{$pl_resend_countdown|intval}"
     data-back-url="{$pl_back_url|escape:'htmlall':'UTF-8'}">

    <a href="{$urls.pages.index}" class="pl-back-to-store">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        {l s='Back to the store' d='Modules.Weprestapasswordlesslogin.Front'}
    </a>

    {* ========== STEP 1: Email ========== *}
    <div class="pl-step" data-step="email">
        <div class="pl-card">

            {if $pl_show_logo && $pl_shop_logo_url}
                <img src="{$pl_shop_logo_url|escape:'htmlall':'UTF-8'}"
                     alt="{$pl_shop_name|escape:'htmlall':'UTF-8'}"
                     class="pl-logo">
            {/if}

            {if $pl_from_checkout}
                <p class="pl-checkout-message">
                    {l s='Sign in to complete your order' d='Modules.Weprestapasswordlesslogin.Front'}
                </p>
            {/if}

            <h1 class="pl-title">{l s='Sign in or create an account' d='Modules.Weprestapasswordlesslogin.Front'}</h1>

            {if $pl_google_enabled}
                <div id="pl-google-btn-container" class="pl-google-container">
                    <button type="button" class="pl-btn pl-btn--google" id="pl-google-btn">
                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
                            <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                        </svg>
                        {l s='Continue with Google' d='Modules.Weprestapasswordlesslogin.Front'}
                    </button>
                </div>

                <div class="pl-divider">
                    <span>{l s='or' d='Modules.Weprestapasswordlesslogin.Front'}</span>
                </div>
            {/if}

            <form class="pl-form" id="pl-email-form" novalidate>
                <div class="pl-field">
                    <input type="email"
                           id="pl-email"
                           name="email"
                           required
                           placeholder="{l s='Email address' d='Modules.Weprestapasswordlesslogin.Front'}"
                           class="pl-input"
                           autocomplete="email"
                           autofocus>
                </div>
                <button type="submit" class="pl-btn pl-btn--primary" id="pl-send-code-btn">
                    <span class="pl-btn__text">{l s='Continue' d='Modules.Weprestapasswordlesslogin.Front'}</span>
                    <span class="pl-btn__loader" style="display:none;"></span>
                </button>
            </form>

            {if $pl_show_classic_login}
                <div class="pl-classic-link">
                    <a href="{$pl_login_url|escape:'htmlall':'UTF-8'}">
                        {l s='Sign in with a password' d='Modules.Weprestapasswordlesslogin.Front'}
                    </a>
                </div>
            {/if}
        </div>
    </div>

    {* ========== STEP 2: Code verification ========== *}
    <div class="pl-step" data-step="code" style="display:none;">
        <div class="pl-card">
            <button type="button" class="pl-back" id="pl-back-to-email" aria-label="{l s='Back' d='Modules.Weprestapasswordlesslogin.Front'}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </button>

            <h1 class="pl-title">{l s='Enter your code' d='Modules.Weprestapasswordlesslogin.Front'}</h1>
            <p class="pl-subtitle" id="pl-code-subtitle"></p>

            <div class="pl-code-inputs" id="pl-code-inputs">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="0" autocomplete="one-time-code">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="1">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="2">
                <span class="pl-code-separator"></span>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="3">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="4">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="pl-code-digit" data-index="5">
            </div>

            <div class="pl-error" id="pl-code-error" style="display:none;"></div>

            <div class="pl-resend">
                <button type="button" class="pl-resend-btn" id="pl-resend-btn" disabled>
                    {l s='Resend code' d='Modules.Weprestapasswordlesslogin.Front'}
                    <span id="pl-countdown-wrap">(<span id="pl-countdown"></span>)</span>
                </button>
            </div>
        </div>
    </div>

    {* ========== STEP 3: Profile completion ========== *}
    <div class="pl-step" data-step="profile" style="display:none;">
        <div class="pl-card">
            <button type="button" class="pl-back" id="pl-back-to-code" aria-label="{l s='Back' d='Modules.Weprestapasswordlesslogin.Front'}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </button>

            <h1 class="pl-title">{l s='Complete your profile' d='Modules.Weprestapasswordlesslogin.Front'}</h1>
            <p class="pl-subtitle">{l s='Just a few details to get started' d='Modules.Weprestapasswordlesslogin.Front'}</p>

            <form class="pl-form" id="pl-profile-form" novalidate>
                <div class="pl-field">
                    <input type="text"
                           id="pl-firstname"
                           name="firstname"
                           required
                           placeholder="{l s='First name' d='Modules.Weprestapasswordlesslogin.Front'}"
                           class="pl-input"
                           autocomplete="given-name">
                </div>
                <div class="pl-field">
                    <input type="text"
                           id="pl-lastname"
                           name="lastname"
                           required
                           placeholder="{l s='Last name' d='Modules.Weprestapasswordlesslogin.Front'}"
                           class="pl-input"
                           autocomplete="family-name">
                </div>
                <button type="submit" class="pl-btn pl-btn--primary" id="pl-complete-btn">
                    <span class="pl-btn__text">{l s='Complete' d='Modules.Weprestapasswordlesslogin.Front'}</span>
                    <span class="pl-btn__loader" style="display:none;"></span>
                </button>
            </form>
        </div>
    </div>

    {* ========== Global message overlay ========== *}
    <div class="pl-message" id="pl-message" style="display:none;"></div>
</div>
