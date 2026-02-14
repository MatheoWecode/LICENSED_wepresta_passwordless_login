{**
 * Module Starter - Hook displayHome
 *
 * Variables disponibles:
 * - $wepresta_passwordless_login.title
 * - $wepresta_passwordless_login.description
 * - $wepresta_passwordless_login.link
 *}

<div class="wepresta_passwordless_login-home">
    <div class="wepresta_passwordless_login-container">
        {if $wepresta_passwordless_login.title}
            <h2 class="wepresta_passwordless_login-title">{$wepresta_passwordless_login.title|escape:'html':'UTF-8'}</h2>
        {/if}

        {if $wepresta_passwordless_login.description}
            <div class="wepresta_passwordless_login-description">
                {$wepresta_passwordless_login.description nofilter}
            </div>
        {/if}

        {if $wepresta_passwordless_login.link}
            <a href="{$wepresta_passwordless_login.link}" class="btn btn-primary wepresta_passwordless_login-btn">
                {l s='En savoir plus' d='Modules.Wepresta\Passwordless\Login.Shop'}
            </a>
        {/if}
    </div>
</div>
