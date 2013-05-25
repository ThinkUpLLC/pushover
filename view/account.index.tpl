<div class="plugin-info">

    <span class="pull-right">{insert name="help_link" id='pushover'}</span>
    <h1>
        <img src="{$site_root_path}plugins/pushover/assets/img/plugin_icon.png" class="plugin-image">
        Pushover Plugin
    </h1>
    
    <p>This plugin sends mobile push notifications of new ThinkUp insights via <a href="http://pushover.net">Pushover</a>.</p>
    <p>To get set up:</p>
    <ol>
    <li>Purchase the Pushover mobile app for <a href="https://pushover.net/clients/android">Android</a> or <a href="https://pushover.net/clients/ios">iOS</a>.</li>
    <li>Create an account and register your device at <a href="http://pushover.net">Pushover.net</a>.</li>
    <li>Create a new application on Pushover.net called ThinkUp, and upload <a href="{$site_root_path}plugins/pushover/assets/img/thinkup-pushover-logo.png" target="_blank">this icon</a> for it.</li>
    <li>Save your Pushover app token and user key below.</li>
    </ol>
    <p>Next time ThinkUp generates new insights, it will send push notifications via Pushover.</p>

</div>

{if $user_is_admin}

    {include file="_usermessage.tpl" field="setup"}

    {$options_markup}

{/if}

