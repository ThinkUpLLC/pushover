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
require_once THINKUP_WEBAPP_PATH.'plugins/pushover/extlib/Pushover.php';

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
        //set up logging
        $logger = Logger::getInstance();
        $logger->logUserSuccess("Starting Pushover notifications push", __METHOD__.','.__LINE__);

        //get plugin settings
        $plugin_option_dao = DAOFactory::getDAO('PluginOptionDAO');
        $options = $plugin_option_dao->getOptionsHash('pushover', true);
        $pushover_user_key = isset($options['pushover_user_key']) ? $options['pushover_user_key']->option_value : null;
        $pushover_app_token =
        isset($options['pushover_app_token']) ? $options['pushover_app_token']->option_value : null;

        if (isset($pushover_user_key) && isset($pushover_app_token)) {
            //Get the creation time of the last insight Pushover notification sent, stored in options table
            $options = $plugin_option_dao->getOptionsHash('pushover');
            if (isset($options['last_pushed_insight_creation_date']->option_value)) {
                $last_pushed_insight_creation_date = $options['last_pushed_insight_creation_date']->option_value;
            } else {
                $last_pushed_insight_creation_date = false;
            }
            $owner_dao = DAOFactory::getDAO('OwnerDAO');
            $owner = $owner_dao->getByEmail(Session::getLoggedInUser());

            //Get insights since last pushed ID, or latest insight
            $insight_dao = DAOFactory::getDAO('InsightDAO');
            $insights = array();
            if ($last_pushed_insight_creation_date !== false ) {
                //Get insights since last pushed insight creation date
                $insights = $insight_dao->getAllOwnerInstanceInsightsSince($owner->id,
                $last_pushed_insight_creation_date);
            } else {
                // get last insight generated
                $insights = $insight_dao->getAllOwnerInstanceInsights($owner->id, $page_count=1);
            }
            // If there are more than 5 to notify about since then, send those individually
            if (sizeof($insights) > 0) {
                $push = new Pushover();
                $push->setToken($pushover_app_token);
                $push->setUser($pushover_user_key);
                $push->setUrl(Utils::getApplicationURL());
                $cfg = Config::getInstance();
                $app_title = $cfg->getValue('app_title_prefix').'ThinkUp';
                $push->setUrlTitle($app_title);
                foreach ($insights as $insight) {
                    $username_in_title = (($insight->instance->network == 'twitter')?'@':'') .
                    $insight->instance->network_username;
                    $title = str_replace(':', '', $insight->prefix). " (".$username_in_title .")";
                    $push->setTitle($title);
                    $push->setMessage(strip_tags($insight->text));
                    $push->setDebug(true);
                    $results = $push->send();
                    $logger->logInfo("Push results: ".Utils::varDumpToString($results), __METHOD__.','.__LINE__);
                }
                // Update $last_pushed_insight_creation_date in plugin settings
                if (isset($options['last_pushed_insight_creation_date']->option_id)) {
                    //update option
                    $plugin_option_dao->updateOption($options['last_pushed_insight_creation_date']->option_id,
                    'last_pushed_insight_creation_date', $insights[0]->time_generated);
                } else {
                    //insert option
                    $plugin_dao = DAOFactory::getDAO('PluginDAO');
                    $plugin_id = $plugin_dao->getPluginId('pushover');
                    $plugin_option_dao->insertOption($plugin_id, 'last_pushed_insight_creation_date',
                    $insights[0]->time_generated);
                }
                $logger->logUserSuccess("Pushed ".sizeof($insights)." insights.", __METHOD__.','.__LINE__);
            } else {
                $logger->logInfo("No insights to push.", __METHOD__.','.__LINE__);
            }
        } else {
            $logger->logInfo("Pushover plugin isn't configured for use.", __METHOD__.','.__LINE__);
        }
        $logger->logUserSuccess("Completed Pushover notifications push.", __METHOD__.','.__LINE__);
    }

    public function getDashboardMenuItems($instance) {

    }

    public function getPostDetailMenuItems($post) {

    }
    public function renderInstanceConfiguration($owner, $instance_username, $instance_network) {
        return "";
    }
}
