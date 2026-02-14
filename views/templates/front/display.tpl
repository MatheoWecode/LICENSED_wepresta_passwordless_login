{**
 * Module Starter - Page front display
 *
 * Variables disponibles:
 * - $wepresta_passwordless_login.title
 * - $wepresta_passwordless_login.description
 *}

{extends file='page.tpl'}

{block name='page_title'}
    {$wepresta_passwordless_login.title|escape:'html':'UTF-8'}
{/block}

{block name='page_content'}
    <div class="wepresta_passwordless_login-page">
        <div class="wepresta_passwordless_login-content">
            {if $wepresta_passwordless_login.description}
                <div class="wepresta_passwordless_login-description">
                    {$wepresta_passwordless_login.description nofilter}
                </div>
            {/if}

            <div class="wepresta_passwordless_login-info">
                <p>{l s='Bienvenue sur la page du module.' d='Modules.Wepresta\Passwordless\Login.Shop'}</p>
            </div>
        </div>
    </div>
{/block}

