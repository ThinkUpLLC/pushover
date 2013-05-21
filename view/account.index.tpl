<div class="plugin-info">

    <span class="pull-right">{insert name="help_link" id='pushover'}</span>
    <h1>
        <img src="{$site_root_path}plugins/pushover/assets/img/plugin_icon.png" class="plugin-image">
        Pushover Plugin
    </h1>
    
    <p>{$message}</p>

</div>

{if $user_is_admin}

    {include file="_usermessage.tpl" field="setup"}

    {$options_markup}

{/if}

