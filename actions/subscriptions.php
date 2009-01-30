<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * List of a user's subscriptions
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
 * @category  Social
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * A list of the user's subscriptions
 *
 * @category Social
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

if (!defined('LACONICA')) { exit(1); }

class SubscriptionsAction extends GalleryAction
{
    function title()
    {
        if ($this->page == 1) {
            return sprintf(_('%s subscriptions'), $this->user->nickname);
        } else {
            return sprintf(_('%s subscriptions, page %d'),
                           $this->user->nickname,
                           $this->page);
        }
    }

    function showPageNotice()
    {
        $user =& common_current_user();
        if ($user && ($user->id == $this->profile->id)) {
            $this->element('p', null,
                           _('These are the people whose notices '.
                             'you listen to.'));
        } else {
            $this->element('p', null,
                           sprintf(_('These are the people whose '.
                                     'notices %s listens to.'),
                                   $this->profile->nickname));
        }
    }

    function getAllTags()
    {
        return $this->getTags('subscribed', 'subscriber');
    }

    function showContent()
    {
        parent::showContent();

        $offset = ($this->page-1) * PROFILES_PER_PAGE;
        $limit =  PROFILES_PER_PAGE + 1;

        $cnt = 0;

        if ($this->tag) {
            $subscriptions = $this->user->getTaggedSubscriptions($this->tag, $offset, $limit);
        } else {
            $subscriptions = $this->user->getSubscriptions($offset, $limit);
        }

        if ($subscriptions) {
            $subscriptions_list = new SubscriptionsList($subscriptions, $this->user, $this);
            $cnt = $subscriptions_list->show();
        }

        $subscriptions->free();

        $this->pagination($this->page > 1, $cnt > PROFILES_PER_PAGE,
                          $this->page, 'subscriptions',
                          array('nickname' => $this->user->nickname));
    }
}

class SubscriptionsList extends ProfileList
{
    function showOwnerControls($profile)
    {
        $sub = Subscription::pkeyGet(array('subscriber' => $this->owner->id,
                                           'subscribed' => $profile->id));
        if (!$sub) {
            return;
        }

        $this->out->elementStart('form', array('id' => 'subedit-' . $profile->id,
                                          'method' => 'post',
                                          'class' => 'form_subcription_edit',
                                          'action' => common_local_url('subedit')));
        $this->out->hidden('token', common_session_token());
        $this->out->hidden('profile', $profile->id);
        $this->out->checkbox('jabber', _('Jabber'), $sub->jabber);
        $this->out->checkbox('sms', _('SMS'), $sub->sms);
        $this->out->submit('save', _('Save'));
        $this->out->elementEnd('form');
        return;
    }
}
