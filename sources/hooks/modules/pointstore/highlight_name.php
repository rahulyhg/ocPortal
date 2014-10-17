<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    pointstore
 */
class Hook_pointstore_highlight_name
{
    /**
     * Standard pointstore item initialisation function.
     */
    public function init()
    {
        require_lang('ocf');
    }

    /**
     * Standard pointstore item initialisation function.
     *
     * @return array                    The "shop fronts"
     */
    public function info()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        if ((get_option('is_on_' . $class . '_buy') == '0') || (get_forum_type() != 'ocf')) {
            return array();
        }
        if ($GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(), 'm_highlighted_name') == 1) {
            return array();
        }
        if (get_option('enable_highlight_name') == '0') {
            return array();
        }

        $next_url = build_url(array('page' => '_SELF', 'type' => 'action', 'id' => $class), '_SELF');
        return array(do_template('POINTSTORE_' . strtoupper($class), array('NEXT_URL' => $next_url)));
    }

    /**
     * Standard interface stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        if ((get_option('is_on_' . $class . '_buy') == '0') || (get_forum_type() != 'ocf')) {
            return new ocp_tempcode();
        }
        if ($GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(), 'm_highlighted_name') == 1) {
            warn_exit(do_lang_tempcode('_ALREADY_HAVE'));
        }

        $title = get_screen_title('NAME_HIGHLIGHTING');

        $cost = intval(get_option($class));
        $next_url = build_url(array('page' => '_SELF', 'type' => 'action_done', 'id' => $class), '_SELF');
        $points_left = available_points(get_member());

        // Check points
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        return do_template('POINTSTORE_HIGHLIGHT_NAME_SCREEN', array('_GUID' => 'fec7bedd71e57170f63257b95da43c93', 'TITLE' => $title, 'COST' => integer_format($cost), 'REMAINING' => integer_format($points_left - $cost), 'NEXT_URL' => $next_url));
    }

    /**
     * Standard actualisation stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action_done()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        if ((get_option('is_on_' . $class . '_buy') == '0') || (get_forum_type() != 'ocf')) {
            return new ocp_tempcode();
        }
        if ($GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(), 'm_highlighted_name') == 1) {
            warn_exit(do_lang_tempcode('_ALREADY_HAVE'));
        }

        $title = get_screen_title('NAME_HIGHLIGHTING');

        post_param_integer('confirm'); // To make sure we're not being passed by a GET

        // Check points
        $cost = intval(get_option($class));
        $points_left = available_points(get_member());
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        // Actuate
        $GLOBALS['FORUM_DB']->query_update('f_members', array('m_highlighted_name' => 1), array('id' => get_member()), '', 1);
        require_code('points2');
        charge_member(get_member(), $cost, do_lang('NAME_HIGHLIGHTING'));
        $GLOBALS['SITE_DB']->query_insert('sales', array('date_and_time' => time(), 'memberid' => get_member(), 'purchasetype' => 'NAME_HIGHLIGHTING', 'details' => '', 'details2' => ''));

        // Show message
        $url = build_url(array('page' => '_SELF', 'type' => 'misc'), '_SELF');
        return redirect_screen($title, $url, do_lang_tempcode('ORDER_GENERAL_DONE'));
    }
}
