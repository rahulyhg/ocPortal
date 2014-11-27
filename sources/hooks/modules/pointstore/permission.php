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
class Hook_pointstore_permission
{
    /**
     * Standard pointstore item initialisation function.
     */
    public function init()
    {
        $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'member_privileges WHERE active_until IS NOT NULL AND active_until<' . strval(time()));
        $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'member_category_access WHERE active_until IS NOT NULL AND active_until<' . strval(time()));
        $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'member_page_access WHERE active_until IS NOT NULL AND active_until<' . strval(time()));
        $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'member_zone_access WHERE active_until IS NOT NULL AND active_until<' . strval(time()));
    }

    /**
     * Get fields for adding/editing one of these.
     *
     * @param  string                   $name_suffix What to place onto the end of the field name
     * @param  SHORT_TEXT               $title Title
     * @param  LONG_TEXT                $description Description
     * @param  BINARY                   $enabled Whether it is enabled
     * @param  ?integer                 $cost The cost in points (null: not set)
     * @param  ?integer                 $hours Number of hours for it to last for (null: unlimited)
     * @param  ID_TEXT                  $type Permission scope 'type'
     * @param  ID_TEXT                  $privilege Permission scope 'privilege'
     * @param  ID_TEXT                  $zone Permission scope 'zone'
     * @param  ID_TEXT                  $page Permission scope 'page'
     * @param  ID_TEXT                  $module Permission scope 'module'
     * @param  ID_TEXT                  $category Permission scope 'category'
     * @param  SHORT_TEXT               $mail_subject Confirmation mail subject
     * @param  LONG_TEXT                $mail_body Confirmation mail body
     * @return tempcode                 The fields
     */
    public function get_fields($name_suffix = '', $title = '', $description = '', $enabled = 1, $cost = null, $hours = null, $type = 'member_privileges', $privilege = '', $zone = '', $page = '', $module = '', $category = '', $mail_subject = '', $mail_body = '')
    {
        require_lang('points');

        $fields = new Tempcode();

        $fields->attach(form_input_line(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_TITLE'), 'permission_title' . $name_suffix, $title, true));
        $fields->attach(form_input_text(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_DESCRIPTION'), 'permission_description' . $name_suffix, $description, true));
        $fields->attach(form_input_integer(do_lang_tempcode('COST'), do_lang_tempcode('HOW_MUCH_THIS_COSTS'), 'permission_cost' . $name_suffix, $cost, true));
        $fields->attach(form_input_integer(do_lang_tempcode('PERMISSION_HOURS'), do_lang_tempcode('DESCRIPTION_PERMISSION_HOURS'), 'permission_hours' . $name_suffix, $hours, false));
        $fields->attach(form_input_tick(do_lang_tempcode('ENABLED'), '', 'permission_enabled' . $name_suffix, $enabled == 1));

        $types = new Tempcode();
        $_types = array('member_privileges', 'member_zone_access', 'member_page_access', 'member_category_access');
        foreach ($_types as $_type) {
            $types->attach(form_input_list_entry($_type, $type == $_type, do_lang_tempcode('PERM_TYPE_' . $_type)));
        }
        $fields->attach(form_input_list(do_lang_tempcode('PERMISSION_SCOPE_type'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_type'), 'permission_type' . $name_suffix, $types));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'c1ee1d8ff171d8de6cd5ed14b5a59afb', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('SETTINGS'))));

        require_all_lang();
        $privileges = new Tempcode();
        $temp = form_input_list_entry('', false, do_lang_tempcode('NA_EM'));
        $privileges->attach($temp);
        $_privileges = $GLOBALS['SITE_DB']->query_select('privilege_list', array('*'), null, 'ORDER BY p_section,the_name');
        $__privileges = array();
        foreach ($_privileges as $_privilege) {
            $_pt_name = do_lang('PRIVILEGE_' . $_privilege['the_name'], null, null, null, null, false);
            if (is_null($_pt_name)) {
                continue;
            }
            $__privileges[$_privilege['the_name']] = $_pt_name;
        }
        natsort($__privileges);
        foreach (array_keys($__privileges) as $__privilege) {
            $pt_name = do_lang_tempcode('PRIVILEGE_' . $__privilege);
            $temp = form_input_list_entry($__privilege, $__privilege == $privilege, $pt_name);
            $privileges->attach($temp);
        }
        $fields->attach(form_input_list(do_lang_tempcode('PERMISSION_SCOPE_privilege'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_privilege'), 'permission_privilege' . $name_suffix, $privileges, null, false, false));
        $zones = new Tempcode();
        //$zones->attach(form_input_list_entry('',false,do_lang_tempcode('NA_EM')));      Will always scope to a zone. Welcome zone would be '' anyway, so we're simplifying the code by having a zone setting which won't hurt anyway
        require_code('zones2');
        require_code('zones3');
        $zones->attach(create_selection_list_zones($zone));
        $fields->attach(form_input_list(do_lang_tempcode('PERMISSION_SCOPE_zone'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_zone'), 'permission_zone' . $name_suffix, $zones, null, false, false));
        $pages = new Tempcode();
        $temp = form_input_list_entry('', false, do_lang_tempcode('NA_EM'));
        $pages->attach($temp);
        $_zones = find_all_zones();
        $_pages = array();
        foreach ($_zones as $_zone) {
            $_pages += find_all_pages_wrap($_zone);
        }
        foreach (array_keys($_pages) as $_page) {
            if (is_integer($_page)) {
                $_page = strval($_page); // PHP array combining weirdness
            }
            $temp = form_input_list_entry($_page, $page == $_page);
            $pages->attach($temp);
        }
        $fields->attach(form_input_list(do_lang_tempcode('PERMISSION_SCOPE_page'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_page'), 'privilege_page' . $name_suffix, $pages, null, false, false));
        $modules = new Tempcode();
        $temp = form_input_list_entry('', false, do_lang_tempcode('NA_EM'));
        $modules->attach($temp);
        $_modules = find_all_hooks('systems', 'module_permissions');
        foreach (array_keys($_modules) as $_module) {
            $temp = form_input_list_entry($_module, $_module == $module);
            $modules->attach($temp);
        }
        $fields->attach(form_input_list(do_lang_tempcode('PERMISSION_SCOPE_module'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_module'), 'permission_module' . $name_suffix, $modules, null, false, false));
        $fields->attach(form_input_line(do_lang_tempcode('PERMISSION_SCOPE_category'), do_lang_tempcode('DESCRIPTION_PERMISSION_SCOPE_category'), 'permission_category' . $name_suffix, $category, false));

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'b89804ab98762d661f4337b1dfb62d46', 'SECTION_HIDDEN' => false, 'TITLE' => do_lang_tempcode('PURCHASE_MAIL'), 'HELP' => do_lang_tempcode('DESCRIPTION_PURCHASE_MAIL'))));
        $fields->attach(form_input_line(do_lang_tempcode('PURCHASE_MAIL_SUBJECT'), '', 'permission_mail_subject' . $name_suffix, $mail_subject, false));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('PURCHASE_MAIL_BODY'), '', 'permission_mail_body' . $name_suffix, $mail_body, false));

        return $fields;
    }

    /**
     * Standard pointstore item configuration function.
     *
     * @return ?array                   A tuple: list of [fields to shown, hidden fields], title for add form, add form (null: disabled)
     */
    public function config()
    {
        $fields = new Tempcode();
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_permissions', array('*'), null, 'ORDER BY id');
        $hidden = new Tempcode();
        $out = array();
        foreach ($rows as $i => $row) {
            $fields = new Tempcode();
            $hidden = new Tempcode();
            $hours = $row['p_hours'];
            if ($hours == 400000) {
                $hours = null; // LEGACY: Around 100 years, but meaning unlimited
            }
            $fields->attach($this->get_fields('_' . strval($i), get_translated_text($row['p_title']), get_translated_text($row['p_description']), $row['p_enabled'], $row['p_cost'], $hours, $row['p_type'], $row['p_privilege'], $row['p_zone'], $row['p_page'], $row['p_module'], $row['p_category'], get_translated_text($row['p_mail_subject']), get_translated_text($row['p_mail_body'])));
            $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '4055cbfc1c94723f4ad72a80ede0b554', 'TITLE' => do_lang_tempcode('ACTIONS'))));
            $fields->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE'), 'delete_permission_' . strval($i), false));
            $hidden->attach(form_input_hidden('permission_' . strval($i), strval($row['id'])));
            $out[] = array($fields, $hidden, do_lang_tempcode('_EDIT_PERMISSION_PRODUCT', escape_html(get_translated_text($row['p_title']))));
        }

        return array($out, do_lang_tempcode('ADD_NEW_PERMISSION_PRODUCT'), $this->get_fields(), do_lang_tempcode('PERMISSION_PRODUCT_DESCRIPTION'));
    }

    /**
     * Standard pointstore item configuration save function.
     */
    public function save_config()
    {
        $i = 0;
        $rows = list_to_map('id', $GLOBALS['SITE_DB']->query_select('pstore_permissions', array('*')));
        while (array_key_exists('permission_' . strval($i), $_POST)) {
            $id = post_param_integer('permission_' . strval($i));
            $title = post_param('permission_title_' . strval($i));
            $description = post_param('permission_description_' . strval($i));
            $enabled = post_param_integer('permission_enabled_' . strval($i), 0);
            $cost = post_param_integer('permission_cost_' . strval($i));
            $hours = post_param_integer('permission_hours_' . strval($i), null);
            $type = post_param('permission_type_' . strval($i));
            $privilege = post_param('permission_privilege_' . strval($i));
            $zone = post_param('permission_zone_' . strval($i));
            $page = post_param('privilege_page_' . strval($i));
            $module = post_param('permission_module_' . strval($i));
            $category = post_param('permission_category_' . strval($i));
            $mail_subject = post_param('permission_mail_subject_' . strval($i));
            $mail_body = post_param('permission_mail_body_' . strval($i));

            $delete = post_param_integer('delete_permission_' . strval($i), 0);

            $_title = $rows[$id]['p_title'];
            $_description = $rows[$id]['p_description'];
            $_mail_subject = $rows[$id]['p_mail_subject'];
            $_mail_body = $rows[$id]['p_mail_body'];

            if ($delete == 1) {
                delete_lang($_title);
                delete_lang($_description);
                delete_lang($_mail_subject);
                delete_lang($_mail_body);
                $GLOBALS['SITE_DB']->query_delete('pstore_permissions', array('id' => $id), '', 1);
            } else {
                $map = array(
                    'p_enabled' => $enabled,
                    'p_cost' => $cost,
                    'p_hours' => $hours,
                    'p_type' => $type,
                    'p_privilege' => $privilege,
                    'p_zone' => $zone,
                    'p_page' => $page,
                    'p_module' => $module,
                    'p_category' => $category,
                );
                $map += lang_remap('p_title', $_title, $title);
                $map += lang_remap_comcode('p_description', $_description, $description);
                $map += lang_remap('p_mail_subject', $_mail_subject, $mail_subject);
                $map += lang_remap('p_mail_body', $_mail_body, $mail_body);
                $GLOBALS['SITE_DB']->query_update('pstore_permissions', $map, array('id' => $id), '', 1);
            }
            $i++;
        }
        $title = post_param('permission_title', null);
        if (!is_null($title)) {
            $description = post_param('permission_description');
            $enabled = post_param_integer('permission_enabled', 0);
            $cost = post_param_integer('permission_cost');
            $hours = post_param_integer('permission_hours', null);
            $type = post_param('permission_type');
            $privilege = post_param('permission_privilege');
            $zone = post_param('permission_zone');
            $page = post_param('privilege_page');
            $module = post_param('permission_module');
            $category = post_param('permission_category');
            $mail_subject = post_param('permission_mail_subject');
            $mail_body = post_param('permission_mail_body');

            $map = array(
                'p_enabled' => $enabled,
                'p_cost' => $cost,
                'p_hours' => $hours,
                'p_type' => $type,
                'p_privilege' => $privilege,
                'p_zone' => $zone,
                'p_page' => $page,
                'p_module' => $module,
                'p_category' => $category,
            );
            $map += insert_lang('p_title', $title, 2);
            $map += insert_lang_comcode('p_description', $description, 2);
            $map += insert_lang('p_mail_subject', $mail_subject, 2);
            $map += insert_lang('p_mail_body', $mail_body, 2);
            $GLOBALS['SITE_DB']->query_insert('pstore_permissions', $map);
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
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_permissions', array('*'), array('p_enabled' => 1));
        foreach ($rows as $row) {
            if ($this->bought($row)) {
                continue;
            }

            $next_url = build_url(array('page' => '_SELF', 'type' => 'action', 'id' => $class, 'sub_id' => $row['id']), '_SELF');
            $items[] = do_template('POINTSTORE_' . strtoupper($class), array('NEXT_URL' => $next_url, 'TITLE' => get_translated_text($row['p_title']), 'DESCRIPTION' => get_translated_tempcode('pstore_permissions', $row, 'p_description')));
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
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_permissions', array('p_title', 'p_cost'), array('id' => $id, 'p_enabled' => 1));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        $p_title = get_translated_text($rows[0]['p_title']);
        $title = get_screen_title('PURCHASE_SOME_PRODUCT', true, array($p_title));

        $cost = $rows[0]['p_cost'];
        $next_url = build_url(array('page' => '_SELF', 'type' => 'action_done', 'id' => $class, 'sub_id' => $id), '_SELF');
        $points_left = available_points(get_member());

        // Check points
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        return do_template('POINTSTORE_CUSTOM_ITEM_SCREEN', array('_GUID' => '879bd8389dcd6b4b8e0ec610d76bcb35', 'TITLE' => $title, 'COST' => integer_format($cost), 'REMAINING' => integer_format($points_left - $cost), 'NEXT_URL' => $next_url));
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
        $rows = $GLOBALS['SITE_DB']->query_select('pstore_permissions', array('*'), array('id' => $id, 'p_enabled' => 1));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        $row = $rows[0];

        $cost = $row['p_cost'];

        $p_title = get_translated_text($row['p_title']);
        $title = get_screen_title('PURCHASE_SOME_PRODUCT', true, array($p_title));

        // Check points
        $points_left = available_points(get_member());
        if (($points_left < $cost) && (!has_privilege(get_member(), 'give_points_self'))) {
            return warn_screen($title, do_lang_tempcode('_CANT_AFFORD', integer_format($cost), integer_format($points_left)));
        }

        // Test to see if it's been bought
        if ($this->bought($row)) {
            warn_exit(do_lang_tempcode('_ALREADY_HAVE'));
        }

        require_code('points2');
        charge_member(get_member(), $cost, $p_title);
        $GLOBALS['SITE_DB']->query_insert('sales', array('date_and_time' => time(), 'memberid' => get_member(), 'purchasetype' => 'PURCHASE_PERMISSION_PRODUCT', 'details' => $p_title, 'details2' => strval($row['id'])));

        // Actuate
        $map = $this->get_map($row);
        $map['active_until'] = is_null($row['p_hours']) ? null : (time() + $row['p_hours'] * 60 * 60);
        $GLOBALS['SITE_DB']->query_insert(filter_naughty_harsh($row['p_type']), $map);

        $member = get_member();

        // Email member
        require_code('mail');
        $subject_line = get_translated_text($row['p_mail_subject']);
        if ($subject_line != '') {
            $message_raw = get_translated_text($row['p_mail_body']);
            $email = $GLOBALS['FORUM_DRIVER']->get_member_email_address($member);
            $to_name = $GLOBALS['FORUM_DRIVER']->get_username($member, true);
            mail_wrap($subject_line, $message_raw, array($email), $to_name, '', '', 3, null, false, null, true);
        }

        // Show message
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($title, $url, do_lang_tempcode('ORDER_GENERAL_DONE'));
    }

    /**
     * Get a database map for our permission row.
     *
     * @param  array                    $row Map row of item
     * @return array                    Permission map row
     */
    public function get_map($row)
    {
        $map = array('member_id' => get_member());
        switch ($row['p_type']) {
            case 'member_privileges':
                $map['privilege'] = $row['p_privilege'];
                $map['the_page'] = $row['p_page'];
                $map['module_the_name'] = $row['p_module'];
                $map['category_name'] = $row['p_category'];
                $map['the_value'] = '1';
                break;
            case 'member_category_access':
                $map['module_the_name'] = $row['p_module'];
                $map['category_name'] = $row['p_category'];
                break;
            case 'member_page_access':
                $map['zone_name'] = $row['p_zone'];
                $map['page_name'] = $row['p_page'];
                break;
            case 'member_zone_access':
                $map['zone_name'] = $row['p_zone'];
                break;
        }
        return $map;
    }

    /**
     * Standard actualisation stage of pointstore item purchase.
     *
     * @param  array                    $row Map row
     * @return boolean                  Whether the current member has bought it already
     */
    public function bought($row)
    {
        $map = $this->get_map($row);
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there(filter_naughty_harsh($row['p_type']), 'member_id', $map);
        return (!is_null($test));
    }
}
