<?php
/**
 *
 * webapp/plugins/pushover/model/class.PushoverPlugin.php
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

class PushoverPlugin extends Plugin implements CrawlerPlugin {

    public function __construct($vals=null) {
        parent::__construct($vals);
        $this->folder_name = 'pushover';
        $this->addRequiredSetting('pushover_user_key');
        $this->addRequiredSetting('pushover_app_token');

    }

    public function activate() {

    }

    public function deactivate() {

    }

    public function renderConfiguration($owner) {
        $controller = new PushoverPluginConfigurationController($owner);
        return $controller->go();
    }

    public function crawl() {
        //Get the creation time of the last insight Pushover notification sent, stored in options table
        $plugin_option_dao = DAOFactory::getDAO('PluginOptionDAO');
        $options = $plugin_option_dao->getOptionsHash('pushover');
        if (isset($options['last_pushed_insight_creation_date']->option_value)) {
            $last_pushed_insight_creation_date = $options['last_pushed_insight_creation_date']->option_value;
        } else {
            $last_pushed_insight_creation_date = false;
        }
        //Get insights since last pushed ID, or latest insight
        $insight_dao = DAOFactory::getDAO('InsightDAO');
        $insights = array();
        if ($last_insight_id_pushed !== false ) {
            //@TODO get insights since last pushed insight creation date
        } else {
            //@TODO get last insight generated
        }
        //@TODO If there are more than 5 to notify about since then, send those individually
        //@TODO Otherwise send a single notification saying there are more than 5 new insights
        //@TODO Store most recent notification sent in plugin settings

    }

    public function getDashboardMenuItems($instance) {

    }

    public function getPostDetailMenuItems($post) {

    }
    public function renderInstanceConfiguration($owner, $instance_username, $instance_network) {
        return "";
    }
}
