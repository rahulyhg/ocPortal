<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_notifications
 */

/**
 * Module page class.
 */
class Module_notifications
{
    /**
	 * Find details of the module.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 1;
        $info['locked'] = false;
        return $info;
    }

    /**
	 * Find entry-points available within this module.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
    public function get_entry_points($check_perms = true,$member_id = null,$support_crosslinks = true,$be_deferential = false)
    {
        if (get_forum_type() == 'ocf') {
            return array();
        }
        if ($check_perms && is_guest($member_id)) {
            return array();
        }
        return array(
            'misc' => array('NOTIFICATION_MANAGEMENT','tool_buttons/notifications2'),
            'browse' => array('NOTIFICATIONS','tool_buttons/notifications'),
        );
    }

    public $title;
    public $id;
    public $row;

    /**
	 * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
    public function pre_run()
    {
        $type = get_param('type','misc');

        require_lang('notifications');

        if ($type == 'view') {
            $id = get_param_integer('id');

            $rows = $GLOBALS['SITE_DB']->query_select('digestives_tin',array('*'),array('id' => $id),'',1);
            if (!array_key_exists(0,$rows)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }
            $row = $rows[0];

            $this->title = get_screen_title('NOTIFICATION_VIEW',true,array(escape_html($row['d_subject'])));

            $this->id = $id;
            $this->row = $row;
        } else {
            $this->title = get_screen_title('NOTIFICATIONS');
        }

        return NULL;
    }

    /**
	 * Execute the module.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function run()
    {
        require_code('notifications2');

        $type = get_param('type','misc');

        if ($type == 'browse') {
            return $this->browse();
        }
        if ($type == 'view') {
            return $this->view();
        }

        if ($type == 'misc') {
            return $this->overall();
        }
        if ($type == 'advanced') {
            return $this->advanced();
        }

        return new ocp_tempcode();
    }

    /**
	 * Show an overall notifications UI.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function browse()
    {
        $start = get_param_integer('n_start',0);
        $max = get_param_integer('n_max',50);

        require_code('notification_poller');
        list($notifications,$max_rows) = get_web_notifications($max,$start);

        require_code('templates_pagination');
        $pagination = pagination(do_lang('NOTIFICATIONS'),$start,'n_start',$max,'n_max',$max_rows);

        return do_template('NOTIFICATION_BROWSE_SCREEN',array('_GUID' => '2b503097bcf97b3296c826e87131cf8e','TITLE' => $this->title,
            'NOTIFICATIONS' => $notifications,
            'PAGINATION' => $pagination,
        ));
    }

    /**
	 * Show an overall notifications UI.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function view()
    {
        $id = $this->id;
        $row = $this->row;

        $member_id = $row['d_from_member_id'];
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id,true);
        $url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,true);
        $avatar_url = $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id);

        $_message = get_translated_tempcode('digestives_tin',$row,'d_message');

        if ($row['d_read'] == 0) {
            $GLOBALS['SITE_DB']->query_update('digestives_tin',array('d_read' => 1),array('id' => $id),'',1);
        }

        return do_template('NOTIFICATION_VIEW_SCREEN',array('_GUID' => '0099edc0157ccd4544877e0e0e552dce','TITLE' => $this->title,
            'ID' => strval($row['id']),
            'SUBJECT' => $row['d_subject'],
            'MESSAGE' => $_message,
            'FROM_USERNAME' => $username,
            'FROM_MEMBER_ID' => strval($member_id),
            'FROM_URL' => $url,
            'FROM_AVATAR_URL' => $avatar_url,
            'PRIORITY' => strval($row['d_priority']),
            'DATE_TIMESTAMP' => strval($row['d_date_and_time']),
            'DATE_WRITTEN_TIME' => get_timezoned_time($row['d_date_and_time']),
            'NOTIFICATION_CODE' => $row['d_notification_code'],
            'CODE_CATEGORY' => $row['d_code_category'],
            'HAS_READ' => ($row['d_read'] == 1),
        ));
    }

    /**
	 * Show an overall notifications UI.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function overall()
    {
        $interface = notifications_ui(get_member());

        return do_template('NOTIFICATIONS_MANAGE_SCREEN',array(
            '_GUID' => '3c81043e6fd004baf9a36c68cb47ffe5',
            'TITLE' => $this->title,
            'INTERFACE' => $interface,
            'ACTION_URL' => get_self_url(),
        ));
    }

    /**
	 * Show an advanced notifications UI.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function advanced()
    {
        $notification_code = get_param('notification_code');

        return notifications_ui_advanced($notification_code);
    }
}
