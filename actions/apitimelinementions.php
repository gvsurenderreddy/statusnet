<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Show notices mentioning a user (@nickname)
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
 * @category  API
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/apibareauth.php';

/**
 * Returns the most recent (default 20) mentions (status containing @nickname)
 *
 * @category API
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class ApiTimelineMentionsAction extends ApiBareAuthAction
{

    var $user    = null;
    var $notices = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     *
     */

    function prepare($args)
    {
        parent::prepare($args);

        $this->user = $this->getTargetUser($this->arg('id'));

        if (empty($this->user)) {
            $this->clientError(_('No such user!'), 404, $this->format);
            return;
        }

        $this->notices = $this->getNotices();

        return true;
    }

    /**
     * Handle the request
     *
     * Just show the notices
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);
        $this->showTimeline();
    }

    /**
     * Show the timeline of notices
     *
     * @return void
     */

    function showTimeline()
    {
        $profile = $this->user->getProfile();

        $sitename   = common_config('site', 'name');
        $title      = sprintf(
            _('%1$s / Updates mentioning %2$s'),
            $sitename, $this->user->nickname
        );
        $taguribase = common_config('integration', 'taguri');
        $id         = "tag:$taguribase:Mentions:" . $this->user->id;
        $link       = common_local_url(
            'replies',
            array('nickname' => $this->user->nickname)
        );
        $subtitle   = sprintf(
            _('%1$s updates that reply to updates from %2$s / %3$s.'),
            $sitename, $this->user->nickname, $profile->getBestName()
        );

        switch($this->format) {
        case 'xml':
            $this->showXmlTimeline($this->notices);
            break;
        case 'rss':
            $this->showRssTimeline($this->notices, $title, $link, $subtitle);
            break;
        case 'atom':
            $selfuri = common_root_url() .
                ltrim($_SERVER['QUERY_STRING'], 'p=');
            $this->showAtomTimeline(
                $this->notices, $title, $id, $link, $subtitle,
                null, $selfuri
            );
            break;
        case 'json':
            $this->showJsonTimeline($this->notices);
            break;
        default:
            $this->clientError(_('API method not found!'), $code = 404);
            break;
        }
    }

    /**
     * Get notices
     *
     * @return array notices
     */

    function getNotices()
    {
        $notices = array();

        $notice = $this->user->getReplies(
            ($this->page - 1) * $this->count, $this->count,
            $this->since_id, $this->max_id, $this->since
        );

        while ($notice->fetch()) {
            $notices[] = clone($notice);
        }

        return $notices;
    }

    /**
     * Is this action read only?
     *
     * @param array $args other arguments
     *
     * @return boolean true
     */

    function isReadOnly($args)
    {
        return true;
    }

    /**
     * When was this feed last modified?
     *
     * @return string datestamp of the latest notice in the stream
     */

    function lastModified()
    {
        if (!empty($this->notices) && (count($this->notices) > 0)) {
            return strtotime($this->notices[0]->created);
        }

        return null;
    }

    /**
     * An entity tag for this stream
     *
     * Returns an Etag based on the action name, language, user ID, and
     * timestamps of the first and last notice in the timeline
     *
     * @return string etag
     */

    function etag()
    {
        if (!empty($this->notices) && (count($this->notices) > 0)) {

            $last = count($this->notices) - 1;

            return '"' . implode(
                ':',
                array($this->arg('action'),
                      common_language(),
                      $this->user->id,
                      strtotime($this->notices[0]->created),
                      strtotime($this->notices[$last]->created))
            )
            . '"';
        }

        return null;
    }

}
