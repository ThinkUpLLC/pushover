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
    /**
     * @var Current Unix timestamp, here for testing.
     */
    var $current_timestamp;

    public function __construct($vals=null) {
        parent::__construct($vals);
        $this->folder_name = 'pushover';
        $this->addRequiredSetting('pushover_user_key');
        $this->addRequiredSetting('pushover_app_token');
        $this->current_timestamp = time();
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
            // Only send pushes after 7am local time
            $owner_dao = DAOFactory::getDAO('OwnerDAO');
            $owner = $owner_dao->getByEmail(Session::getLoggedInUser());
            $tz = $owner->timezone;
            if (empty($tz)) {
                $config = Config::getInstance();
                $tz = $config->getValue('timezone');
            }

            // Is it after 7am local time?
            if (!empty($tz)) {
                $original_tz = date_default_timezone_get();
                date_default_timezone_set($tz);
                $localized_hour = (int)date('G', $this->current_timestamp);
                date_default_timezone_set($original_tz);
            } else {
                $localize_hour = (int)date('G', $this->current_timestamp);
            }
            if ($localized_hour >= 9) {
                //Get the last time Pushover notifications were sent
                $options = $plugin_option_dao->getOptionsHash('pushover');
                if (isset($options['last_push_completion']->option_value)) {
                    $last_push_completion = $options['last_push_completion']->option_value;
                    $logger->logUserInfo("Last push completion was ".$last_push_completion, __METHOD__.','.__LINE__);
                } else {
                    $last_push_completion = false;
                }

                //Get insights since last pushed ID, or latest insight
                $insight_dao = DAOFactory::getDAO('InsightDAO');
                $insights = array();
                if ($owner->is_admin) {
                    if ($last_push_completion !== false ) {
                        //Get all insights since last pushed insight creation date
                        $insights = $insight_dao->getAllInstanceInsightsSince($last_push_completion);
                    } else {
                        // get last insight generated
                        $insights = $insight_dao->getAllInstanceInsights($page_count=1);
                    }
                } else {
                    if ($last_push_completion !== false ) {
                        //Get insights since last pushed insight creation date
                        $insights = $insight_dao->getAllOwnerInstanceInsightsSince($owner->id, $last_push_completion);
                    } else {
                        // get last insight generated
                        $insights = $insight_dao->getAllOwnerInstanceInsights($owner->id, $page_count=1);
                    }
                }
                $total_pushed = 0;
                if (sizeof($insights) > 0) {
                    $logger->logUserInfo("Insight candidates to push, only choosing HIGH emphasis ",
                        __METHOD__.','.__LINE__);
                    $push = new Pushover();
                    $push->setToken($pushover_app_token);
                    $push->setUser($pushover_user_key);
                    $cfg = Config::getInstance();
                    $app_title = $cfg->getValue('app_title_prefix').'ThinkUp';
                    $push->setUrlTitle($app_title);
                    foreach ($insights as $insight) {
                        if ($insight->emphasis == Insight::EMPHASIS_HIGH) {
                            self::pushInsight($insight, $push, $logger);
                            $total_pushed = $total_pushed + 1;
                        }
                    }
                    // If no HIGH insights were pushed (b/c they don't exist) push medium insights
                    if ($total_pushed == 0 ) {
                        $logger->logUserInfo("No high emphasis insights exist, so pushing medium now",
                            __METHOD__.','.__LINE__);
                        foreach ($insights as $insight) {
                            if ($insight->emphasis == Insight::EMPHASIS_MED) {
                                self::pushInsight($insight, $push, $logger);
                                $total_pushed = $total_pushed + 1;
                            }
                        }
                    }

                    // Update $last_push_completion in plugin settings
                    if (isset($options['last_push_completion']->id)) {
                        //update option
                        $result = $plugin_option_dao->updateOption($options['last_push_completion']->id,
                            'last_push_completion', date('Y-m-d H:i:s'));
                        $logger->logInfo("Updated ".$result." option", __METHOD__.','.__LINE__);
                    } else {
                        //insert option
                        $plugin_dao = DAOFactory::getDAO('PluginDAO');
                        $plugin_id = $plugin_dao->getPluginId('pushover');
                        $result = $plugin_option_dao->insertOption($plugin_id, 'last_push_completion',
                            date('Y-m-d H:i:s'));
                        $logger->logInfo("Inserted option ID ".$result, __METHOD__.','.__LINE__);
                    }
                }
                if ($total_pushed > 0) {
                    $logger->logUserSuccess("Pushed ".$total_pushed." insight".(($total_pushed == 1)?'':'s'),
                        __METHOD__.','.__LINE__);
                } else {
                    $logger->logInfo("No insights to push.", __METHOD__.','.__LINE__);
                }
            } else {
                $logger->logInfo("It's too early in the am for push notifications.", __METHOD__.','.__LINE__);
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

    private function pushInsight($insight, $push, $logger) {
        $terms = new InsightTerms($insight->instance->network);
        $title = strip_tags(html_entity_decode($insight->headline));
        $title = $terms->swapInSecondPerson($insight->instance->network_username, $title);
        $push->setTitle($title);
        $message = strip_tags(html_entity_decode($insight->text));
        $message = $terms->swapInSecondPerson($insight->instance->network_username, $message);
        $message = ($message == '')? "See the insight.":$message;
        $push->setMessage($message);
        $insight_date = urlencode(date('Y-m-d', strtotime($insight->date)));
        $push->setUrl(Utils::getApplicationURL()."?u=".$insight->instance->network_username."&n=".
            $insight->instance->network."&d=".$insight_date."&s=". $insight->slug);
        $push->setDebug(false);
        $results = $push->send();
        $logger->logInfo("Push details: ".Utils::varDumpToString($push), __METHOD__.','.__LINE__);
        $logger->logInfo("Push results: ".Utils::varDumpToString($results),
            __METHOD__.','.__LINE__);
    }
}
