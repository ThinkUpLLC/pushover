<?php
/**
 *
 * webapp/plugins/pushover/controller/class.PushoverPluginConfigurationController.php
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkup.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * Pushover (name of file)
 *
 * Description of what this class does
 *
 * Copyright (c) 2013 Gina Trapani
 *
 * @author Gina Trapani <ginatrapani [at] gmail [dot] com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2013 Gina Trapani
 */

class PushoverPluginConfigurationController extends PluginConfigurationController {

    public function __construct($owner) {
        parent::__construct($owner, 'pushover');
        $this->disableCaching();
        $this->owner = $owner;
    }

    public function authControl() {
        $config = Config::getInstance();
        Loader::definePathConstants();
        $this->setViewTemplate( THINKUP_WEBAPP_PATH.'plugins/pushover/view/account.index.tpl');
        $this->addToView('message', 'Hello ThinkUp world! This is an auto-generated plugin configuration '.
        'page for ' . $this->owner->email .'.');
        $this->view_mgr->addHelp('pushover', 'contribute/developers/plugins/buildplugin');

        /** set option fields **/
        $user_key_field = array('name' => 'pushover_user_key', 'label' => 'Your Pushover user token', 'size' => 40);
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $user_key_field); // add element
        $this->addPluginOptionRequiredMessage('pushover_user_key', 'Please enter your Pushover user key.');

        $app_token_field = array('name' => 'pushover_app_token', 'label' => 'Your Pushover app token', 'size' => 50);
        $this->addPluginOption(self::FORM_TEXT_ELEMENT, $app_token_field); // add element
        $this->addPluginOptionRequiredMessage('pushover_app_token', 'Please enter your Pushover app token.');

        $plugin = new PushoverPlugin();
        $this->addToView('is_configured', $plugin->isConfigured());

        return $this->generateView();
    }

}
