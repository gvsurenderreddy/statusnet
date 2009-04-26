<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Plugin to do "real time" updates using Comet/Bayeux
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * Plugin to do realtime updates using Comet
 *
 * @category Plugin
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class CometPlugin extends Plugin
{
    var $server = null;

    function __construct($server=null)
    {
        $this->server = $server;

        parent::__construct();
    }

    function onEndShowScripts($action)
    {
        $timeline = null;

        $this->log(LOG_DEBUG, 'got action ' . $action->trimmed('action'));

        switch ($action->trimmed('action')) {
         case 'public':
            $timeline = '/timelines/public';
            break;
         default:
            return true;
        }

        $scripts = array('jquery.comet.js', 'json2.js', 'updatetimeline.js');

        foreach ($scripts as $script) {
            $action->element('script', array('type' => 'text/javascript',
                                             'src' => common_path('plugins/Comet/'.$script)),
                         ' ');
        }

        $action->elementStart('script', array('type' => 'text/javascript'));
        $action->raw("$(document).ready(function() { updater.init(\"$this->server\", \"$timeline\");});");
        $action->elementEnd('script');

        return true;
    }

    function onEndNoticeSave($notice)
    {
        $this->log(LOG_INFO, "Called for save notice.");

        $timelines = array();

        // XXX: Add other timelines; this is just for the public one

        if ($notice->is_local ||
            ($notice->is_local == 0 && !common_config('public', 'localonly'))) {
            $timelines[] = '/timelines/public';
        }

        if (count($timelines) > 0) {
            // Require this, since we need it
            require_once(INSTALLDIR.'/plugins/Comet/bayeux.class.inc.php');

            $json = $this->noticeAsJson($notice);

            // Bayeux? Comet? Huh? These terms confuse me
            $bay = new Bayeux($this->server);

            foreach ($timelines as $timeline) {
                $this->log(LOG_INFO, "Posting notice $notice->id to '$timeline'.");
                $bay->publish($timeline, $json);
            }

            $bay = NULL;
        }

        return true;
    }

    function noticeAsJson($notice)
    {
        // FIXME: this code should be abstracted to a neutral third
        // party, like Notice::asJson(). I'm not sure of the ethics
        // of refactoring from within a plugin, so I'm just abusing
        // the TwitterApiAction method. Don't do this unless you're me!

        require_once(INSTALLDIR.'/lib/twitterapi.php');

        $act = new TwitterApiAction('/dev/null');

        $arr = $act->twitter_status_array($notice, true);
        $arr['url'] = $notice->bestUrl();

        $profile = $notice->getProfile();
        $arr['user']['profile_url'] = $profile->profileurl;

        return $arr;
    }

    // Push this up to Plugin

    function log($level, $msg)
    {
        common_log($level, get_class($this) . ': '.$msg);
    }
}
