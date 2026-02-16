{**
 * Passwordless Login - Fullscreen auth layout
 * Extends the full-width layout but removes header, footer, breadcrumb
 *}

{extends file='layouts/layout-full-width.tpl'}

{block name='header'}
    {* Empty - no header *}
{/block}

{block name='notifications'}
    {* Empty *}
{/block}

{block name='breadcrumb'}
    {* Empty *}
{/block}

{block name='content_wrapper'}
    <div id="content-wrapper">
        {include file='module:wepresta_passwordless_login/views/templates/front/auth-app.tpl'}
    </div>
{/block}

{block name='footer'}
    {* Empty - no footer *}
{/block}
