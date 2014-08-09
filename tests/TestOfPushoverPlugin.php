<?php
/**
 *
 * webapp/plugins/pushover/tests/TestOfPushoverPlugin.php
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

require_once 'tests/init.tests.php';
require_once THINKUP_ROOT_PATH.'webapp/_lib/extlib/simpletest/autorun.php';
require_once THINKUP_ROOT_PATH.'webapp/config.inc.php';
require_once THINKUP_ROOT_PATH.'tests/classes/class.ThinkUpBasicUnitTestCase.php';
require_once THINKUP_ROOT_PATH. 'webapp/plugins/Pushover/model/class.PushoverPlugin.php';

class TestOfPushoverPlugin extends ThinkUpUnitTestCase {

    public function setUp(){
        parent::setUp();
        $webapp_plugin_registrar = PluginRegistrarWebapp::getInstance();
        $webapp_plugin_registrar->registerPlugin('Pushover', 'PushoverPlugin');
        $webapp_plugin_registrar->setActivePlugin('Pushover');
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testConstructor() {
        $plugin = new PushoverPlugin();
        $this->assertNotNull($plugin);
        $this->assertIsA($plugin, 'PushoverPlugin');
        $this->assertEqual(count($plugin->required_settings), 2);
        $this->assertFalse($plugin->isConfigured());
    }

    public function testCrawlerNotConfigured() {
        $plugin = new PushoverPlugin();
        $plugin->crawl();
        //@TODO check log for 'Pushover plugin isn't configured for use'
    }

    public function testCrawlerConfiguredNoInsightsToPush() {
        $builders = array();
        $builders[] = FixtureBuilder::build('plugins', array('name'=>'Pushover', 'folder_name'=>'pushover'));
        $plugin_dao = new PluginMySQLDAO();
        $plugin_id = $plugin_dao->getPluginId('pushover');

        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_app_token', 'option_value'=>'asdf'));
        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_user_key', 'option_value'=>'qwerty'));

        $plugin = new PushoverPlugin();
        $plugin->crawl();
        //@TODO check log for 'no insights to push'
    }

    public function testCrawlerConfiguredInsightsToPushNonAdmin() {
        $builders = array();
        $builders[] = FixtureBuilder::build('plugins', array('name'=>'Pushover', 'folder_name'=>'pushover'));
        $plugin_dao = new PluginMySQLDAO();
        $plugin_id = $plugin_dao->getPluginId('pushover');

        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_app_token', 'option_value'=>'asdf'));
        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_user_key', 'option_value'=>'qwerty'));

        //owner
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'email'=>'me@example.com', 'is_admin'=>0));
        //instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>1));
        //owner_instance
        $builders[] = FixtureBuilder::build('owner_instances', array('owner_id'=>1, 'instance_id'=>1));
        //insights
        $builders[] = FixtureBuilder::build('insights', array('id'=>1, 'instance_id'=>1, 'text'=>'hallo',
            'related_data'=>null));

        $this->simulateLogin('me@example.com');
        $plugin = new PushoverPlugin();
        $plugin->crawl();
    }

    public function testCrawlerConfiguredInsightsToPushAdmin() {
        $builders = array();
        $builders[] = FixtureBuilder::build('plugins', array('name'=>'Pushover', 'folder_name'=>'pushover'));
        $plugin_dao = new PluginMySQLDAO();
        $plugin_id = $plugin_dao->getPluginId('pushover');

        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_app_token', 'option_value'=>'asdf'));
        $builders[] = FixtureBuilder::build('options', array('namespace'=>'plugin_options-'.$plugin_id,
        'option_name'=>'pushover_user_key', 'option_value'=>'qwerty'));

        //owner
        $builders[] = FixtureBuilder::build('owners', array('id'=>1, 'email'=>'me@example.com', 'is_admin'=>1));
        //instance
        $builders[] = FixtureBuilder::build('instances', array('id'=>1));
        //insights
        $builders[] = FixtureBuilder::build('insights', array('id'=>1, 'instance_id'=>1, 'text'=>'hallo',
            'related_data'=>null, 'emphasis'=>Insight::EMPHASIS_HIGH));

        $this->simulateLogin('me@example.com');
        $plugin = new PushoverPlugin();
        $plugin->crawl();
    }
}
