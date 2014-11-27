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

/**
 * Hook class.
 */
class Hook_pointstore_custom
{
    /**
     * Standard pointstore item initialisation function.
     */
    public function init()
    {
    }

    /**
     * Get fields for adding/editing one of these.
     *
     * @param  string                   $name_suffix What to place onto the end of the field name
     * @param  SHORT_TEXT               $title Title
     * @param  LONG_TEXT                $description Description
     * @param  BINARY                   $enabled Whether it is enabled
     * @param  ?integer                 $cost The cost in points (null: not set)
     * @param  BINARY                   $one_per_member Whether it is restricted to one per member
     * @param  SHORT_TEXT               $mail_subject Confirmation mail subject
     * @param  LONG_TEXT                $mail_body Confirmation mail body
     * @return tempcode                 The fields
     */
    public function get_fields($name_suffix = '', $title = '', $description = '', $enabled = 1, $cost = null, $one_per_member = 0, $mail_subject = '', $mail_body = '')
    {
        require_lang('points');

        $fields = new Tempcode();

        $fields->attach(form_input_line(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_TITLE'), 'custom_title' . $name_suffix, $title, true));
        $fields->attach(form_input_text(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_DESCRIPTION'), 'custom_description' . $name_suffix, $description, true));
        $fields->attach(form_input_integer(do_lang_tempcode('COST'), do_lang_tempcode('HOW_MUCH_THIS_COSTS'), 'custom_cost' . $name_suffix, $cost, true));
        $fields->attach(form_input_tick(do_lang_tempcode('ONE_PER_MEMBER'), do_lang_tempcode('DESCRIPTION_ONE_PER_MEMBER'), 'custom_one_per_member' . $name_suffix, $one_per_member == 1));
        $fields->attach(form_input_tick(do_lang_tempcode('ENABLED'), '', 'custom_enabled' . $name_suffix, $enabled == 1));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '6e4f9d4f6fc7ba05336681c5311bc42f', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('PURCHASE_MAIL'), 'HELP' => do_lang_tempcode('DESCRIPTION_PURCHASE_MAIL'))));
        $fields->attach(form_input_line(do_lang_tempcode('PURCHASE_MAIL_SUBJECT'), '', 'custom_mail_subject' . $name_suffix, $mail_subject, false));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('PURCHASE_MAIL_BODY'), '', 'custom_mail_body' . $name_suffix, $mail_body, false));

        return $fields;
    }

    /**
     * Standard pointstore item configuration function.
     *
     * @return ?array                   A tuple: list of [fields to shown, hidden fields], title for add form, add form (null: disabled)
     */
    public function config()
    {
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_customs', array('*'), null, 'ORDER BY id');
        $out = array();
        foreach ($rows as $i => $row) {
            $fields = new Tempcode();
            $hidden = new Tempcode();
            $fields->attach($this->get_fields('_' . strval($i), get_translated_text($row['c_title']), get_translated_text($row['c_description']), $row['c_enabled'], $row['c_cost'], $row['c_one_per_member'], get_translated_text($row['c_mail_subject']), get_translated_text($row['c_mail_body'])));
            $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '01362c21b40d7905b76ee6134198a128', 'TITLE' => do_lang_tempcode('ACTIONS'))));
            $fields->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE'), 'delete_custom_' . strval($i), false));
            $hidden->attach(form_input_hidden('custom_' . strval($i), strval($row['id'])));
            $out[] = array($fields, $hidden, do_lang_tempcode('_EDIT_CUSTOM_PRODUCT', escape_html(get_translated_text($row['c_title']))));
        }

        return array($out, do_lang_tempcode('ADD_NEW_CUSTOM_PRODUCT'), $this->get_fields(), do_lang_tempcode('CUSTOM_PRODUCT_DESCRIPTION'));
    }

    /**
     * Standard pointstore item configuration save function.
     */
    public function save_config()
    {
        $i = 0;
        $rows = list_to_map('id', $GLOBALS['SITE_DB']->query_select('pstore_customs', array('*')));
        while (array_key_exists('custom_' . strval($i), $_POST)) {
            $id = post_param_integer('custom_' . strval($i));
            $title = post_param('custom_title_' . strval($i));
            $description = post_param('custom_description_' . strval($i));
            $enabled = post_param_integer('custom_enabled_' . strval($i), 0);
            $cost = post_param_integer('custom_cost_' . strval($i));
            $one_per_member = post_param_integer('custom_one_per_member_' . strval($i), 0);
            $mail_subject = post_param('custom_mail_subject_' . strval($i));
            $mail_body = post_param('custom_mail_body_' . strval($i));

            $delete = post_param_integer('delete_custom_' . strval($i), 0);

            $_title = $rows[$id]['c_title'];
            $_description = $rows[$id]['c_description'];
            $_mail_subject = $rows[$id]['c_mail_subject'];
            $_mail_body = $rows[$id]['c_mail_body'];

            if ($delete == 1) {
                delete_lang($_title);
                delete_lang($_description);
                delete_lang($_mail_subject);
                delete_lang($_mail_body);
                $GLOBALS['SITE_DB']->query_delete('pstore_customs', array('id' => $id), '', 1);
            } else {
                $map = array(
                    'c_enabled' => $enabled,
                    'c_cost' => $cost,
                    'c_one_per_member' => $one_per_member,
                );
                $map += lang_remap('c_title', $_title, $title);
                $map += lang_remap_comcode('c_description', $_description, $description);
                $map += lang_remap('c_mail_subject', $_mail_subject, $mail_subject);
                $map += lang_remap('c_mail_body', $_mail_body, $mail_body);
                $GLOBALS['SITE_DB']->query_update('pstore_customs', $map, array('id' => $id), '', 1);
            }
            $i++;
        }
        $title = post_param('custom_title', null);
        if (!is_null($title)) {
            $description = post_param('custom_description');
            $enabled = post_param_integer('custom_enabled', 0);
            $cost = post_param_integer('custom_cost');
            $one_per_member = post_param_integer('custom_one_per_member', 0);
            $mail_subject = post_param('custom_mail_subject');
            $mail_body = post_param('custom_mail_body');

            $map = array(
                'c_enabled' => $enabled,
                'c_cost' => $cost,
                'c_one_per_member' => $one_per_member,
            );
            $map += insert_lang('c_title', $title, 2);
            $map += insert_lang_comcode('c_description', $description, 2);
            $map += insert_lang('c_mail_subject', $mail_subject, 2);
            $map += insert_lang('c_mail_body', $mail_body, 2);
            $GLOBALS['SITE_DB']->query_insert('pstore_customs', $map);
        }
    }

    /**
     * Standard pointstore item initialisation function.
     *
     * @return array                    The "shop fronts"
     */
    public function info()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        $items = array();
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_customs', array('*'), array('c_enabled' => 1));
        foreach ($rows as $row) {
            if ($row['c_one_per_member'] == 1) {
                // Test to see if it's been bought
                $test = $GLOBALS['SITE_DB']->query_select_value_if_there('sales', 'id', array('purchasetype' => 'PURCHASE_CUSTOM_PRODUCT', 'details2' => strval($rows[0]['id']), 'memberid' => get_member()));
                if (!is_null($test)) {
                    continue;
                }
            }

            $next_url = build_url(array('page' => '_SELF', 'type' => 'action', 'id' => $class, 'sub_id' => $row['id']), '_SELF');
            $items[] = do_template('POINTSTORE_' . strtoupper($class), array('NEXT_URL' => $next_url, 'TITLE' => get_translated_text($row['c_title']), 'DESCRIPTION' => get_translated_tempcode('pstore_customs', $row, 'c_description')));
        }
        return $items;
    }

    /**
     * Standard interface stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        $id = get_param_integer('sub_id');
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_customs', array('c_title', 'c_cost', 'c_one_per_member'), array('id' => $id, 'c_enabled' => 1));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        $c_title = get_translated_text($rows[0]['c_title']);
        $title = get_screen_title('PURCHASE_SOME_PRODUCT', true, array($c_title));

        $cost = $rows[0]['c_cost'];
        $next_url = build_url(array('page' => '_SELF', 'type' => 'action_done', 'id' => $class, 'sub_id' => $id), '_SELF');
        $points_left = available_points(get_member());

        // Check points
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        return do_template('POINTSTORE_CUSTOM_ITEM_SCREEN', array('_GUID' => 'bc57d8775b5471935b08f85082ba34ec', 'TITLE' => $title, 'ONE_PER_MEMBER' => ($rows[0]['c_one_per_member'] == 1), 'COST' => integer_format($cost), 'REMAINING' => integer_format($points_left - $cost), 'NEXT_URL' => $next_url));
    }

    /**
     * Standard actualisation stage of pointstore item purchase.
     *
     * @return tempcode                 The UI
     */
    public function action_done()
    {
        $class = str_replace('hook_pointstore_', '', strtolower(get_class($this)));

        post_param_integer('confirm'); // Make sure POSTed
        $id = get_param_integer('sub_id');
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_customs', array('*'), array('id' => $id, 'c_enabled' => 1));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        $row = $rows[0];

        $cost = $row['c_cost'];

        $c_title = get_translated_text($row['c_title']);
        $title = get_screen_title('PURCHASE_SOME_PRODUCT', true, array($c_title));

        // Check points
        $points_left = available_points(get_member());
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        if ($row['c_one_per_member'] == 1) {
            // Test to see if it's been bought
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('sales', 'id', array('purchasetype' => 'PURCHASE_CUSTOM_PRODUCT', 'details2' => strval($row['id']), 'memberid' => get_member()));
            if (!is_null($test)) {
                warn_exit(do_lang_tempcode('ONE_PER_MEMBER_ONLY'));
            }
        }

        require_code('points2');
        charge_member(get_member(), $cost, $c_title);
        $sale_id = $GLOBALS['SITE_DB']->query_insert('sales', array('date_and_time' => time(), 'memberid' => get_member(), 'purchasetype' => 'PURCHASE_CUSTOM_PRODUCT', 'details' => $c_title, 'details2' => strval($row['id'])), true);

        require_code('notifications');
        $subject = do_lang('MAIL_REQUEST_CUSTOM', comcode_escape($c_title), null, null, get_site_default_lang());
        $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        $message_raw = do_lang('MAIL_REQUEST_CUSTOM_BODY', comcode_escape($c_title), $username, null, get_site_default_lang());
        dispatch_notification('pointstore_request_custom', 'custom' . strval($id) . '_' . strval($sale_id), $subject, $message_raw, null, null, 3, true, false, null, null, '', '', '', '', null, true);

        $member = get_member();

        // Email member
        require_code('mail');
        $subject_line = get_translated_text($row['c_mail_subject']);
        if ($subject_line != '') {
            $message_raw = get_translated_text($row['c_mail_body']);
            $email = $GLOBALS['FORUM_DRIVER']->get_member_email_address($member);
            $to_name = $GLOBALS['FORUM_DRIVER']->get_username($member, true);
            mail_wrap($subject_line, $message_raw, array($email), $to_name, '', '', 3, null, false, null, true);
        }

        // Show message
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($title, $url, do_lang_tempcode('ORDER_GENERAL_DONE'));
    }
}
