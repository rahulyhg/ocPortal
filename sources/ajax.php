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
 * @package    core
 */

/*EXTRA FUNCTIONS: simplexml_load_string*/

/**
 * Prepare to inject COR headers.
 */
function cor_prepare()
{
    require_code('input_filter');
    $allowed_partners = get_allowed_partner_sites();
    if (in_array(preg_replace('#^.*://([^:/]*).*$#', '${1}', $_SERVER['HTTP_ORIGIN']), $allowed_partners)) {
        header('Access-Control-Allow-Origin: ' . str_replace("\n", '', str_replace("\r", '', $_SERVER['HTTP_ORIGIN'])));

        if ((isset($_SERVER['REQUEST_METHOD'])) && ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')) {
            header('Access-Control-Allow-Credentials: true');

            // Send pre-flight response
            if (isset($_SERVER['ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header('Access-Control-Allow-Headers: ' . str_replace("\n", '', str_replace("\r", '', $_SERVER['ACCESS_CONTROL_REQUEST_HEADERS'])));
            }
            $methods = 'GET,POST,PUT,HEAD,OPTIONS';
            if (isset($_SERVER['ACCESS_CONTROL_REQUEST_HEADERS'])) {
                $methods .= str_replace("\n", '', str_replace("\r", '', $_SERVER['ACCESS_CONTROL_REQUEST_METHOD']));
            }
            header('Access-Control-Allow-Methods: ' . $methods);

            exit();
        }
    }
}

/**
 * Script to generate a Flash crossdomain file.
 */
function crossdomain_script()
{
    prepare_for_known_ajax_response();

    require_code('xml');

    header('Content-Type: text/xml');

    echo '<' . '?xml version="1.0"?' . '>
<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">
<cross-domain-policy>';
    require_code('input_filter');
    $allowed_partners = get_allowed_partner_sites();
    foreach ($allowed_partners as $post_submitter) {
        $post_submitter = trim($post_submitter);
        if ($post_submitter != '') {
            echo '<allow-access-from domain="' . xmlentities($post_submitter) . '" />';
        }
    }
    echo '
</cross-domain-policy>';
}

/**
 * AJAX script for checking if a new username is valid.
 */
function username_check_script()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/plain');

    require_code('ocf_members_action');
    require_code('ocf_members_action2');
    require_lang('ocf');

    $username = get_param('username', null, true);
    if (!is_null($username)) {
        $username = trim($username);
    }
    $password = either_param('password', null);
    if (!is_null($password)) {
        $password = trim($password);
    }
    $error = ocf_check_name_valid($username, null, $password, true);
    if (!is_null($error)) {
        $error->evaluate_echo();
    }
}

/**
 * AJAX script for checking if a username exists.
 */
function username_exists_script()
{
    prepare_for_known_ajax_response();

    header('Content-type: text/plain; charset=' . get_charset());

    $username = trim(get_param('username', false, true));
    $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
    if (is_null($member_id)) {
        echo 'false';
    }
}

/**
 * AJAX script for allowing username/author/search-terms home-in.
 */
function namelike_script()
{
    prepare_for_known_ajax_response();

    $id = str_replace('*', '%', get_param('id', false, true));
    $special = get_param('special', '');

    @ini_set('ocproducts.xss_detect', '0');

    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="' . get_charset() . '"?' . '>';
    echo '<request><result>';

    if ($special == 'admin_search') {
        $names = array();
        if ($id != '') {
            require_all_lang();
            $hooks = find_all_hooks('systems', 'page_groupings');
            foreach (array_keys($hooks) as $hook) {
                require_code('hooks/systems/page_groupings/' . filter_naughty_harsh($hook));
                $object = object_factory('Hook_page_groupings_' . filter_naughty_harsh($hook), true);
                if (is_null($object)) {
                    continue;
                }
                $info = $object->run();
                foreach ($info as $i) {
                    if (is_null($i)) {
                        continue;
                    }
                    $n = $i[3];
                    $n_eval = is_object($n) ? $n->evaluate() : $n;
                    if ($n_eval == '') {
                        continue;
                    }
                    if ((strpos(strtolower($n_eval), strtolower($id)) !== false) && (has_actual_page_access(get_member(), $i[2][0], $i[2][2]))) {
                        $names[] = '"' . $n_eval . '"';
                    }
                }
            }
            if (count($names) > 10) {
                $names = array();
            }
            sort($names);
        }

        foreach ($names as $name) {
            echo '<option value="' . escape_html($name) . '" displayname="" />';
        }
    } elseif ($special == 'search') {
        $names = array();
        $q = 'SELECT s_primary,COUNT(*) as cnt,MAX(s_num_results) AS s_num_results FROM ' . get_table_prefix() . 'searches_logged WHERE ';
        if ((db_has_full_text($GLOBALS['SITE_DB']->connection_read)) && (method_exists($GLOBALS['SITE_DB']->static_ob, 'db_has_full_text_boolean')) && ($GLOBALS['SITE_DB']->static_ob->db_has_full_text_boolean())) {
            $q .= preg_replace('#\?#', 's_primary', db_full_text_assemble($id, false));
        } else {
            $q .= 's_primary LIKE \'' . /*ideally we would put an % in front, but too slow*/
                db_encode_like($id) . '%\'';
        }
        $q .= ' AND s_primary NOT LIKE \'%<%\' AND ' . db_string_not_equal_to('s_primary', '') . ' GROUP BY s_primary ORDER BY cnt DESC';
        $past_searches = $GLOBALS['SITE_DB']->query($q, 20);
        foreach ($past_searches as $search) {
            if ($search['cnt'] > 5) {
                $names[] = $search['s_primary'];
            }
        }

        foreach ($names as $name) {
            echo '<option value="' . escape_html($name) . '" displayname="" />';
        }
    } else {
        if ((strlen($id) == 0) && (addon_installed('chat'))) {
            $rows = $GLOBALS['SITE_DB']->query_select('chat_friends', array('member_liked'), array('member_likes' => get_member()), 'ORDER BY date_and_time', 100);
            $names = array();
            foreach ($rows as $row) {
                $names[$row['member_liked']] = $GLOBALS['FORUM_DRIVER']->get_username($row['member_liked']);
            }

            foreach ($names as $name) {
                echo '<option value="' . escape_html($name) . '" displayname="" />';
            }
        } else {
            $names = array();
            if ((addon_installed('authors')) && ($special == 'author')) {
                $num_authors = $GLOBALS['SITE_DB']->query_select_value('authors', 'COUNT(*)');
                $like = ($num_authors < 1000) ? db_encode_like('%' . str_replace('_', '\_', $id) . '%') : db_encode_like(str_replace('_', '\_', $id) . '%'); // performance issue
                $rows = $GLOBALS['SITE_DB']->query('SELECT author FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'authors WHERE author LIKE \'' . $like . '\' ORDER BY author', 15);
                $names = collapse_1d_complexity('author', $rows);

                foreach ($names as $name) {
                    echo '<option value="' . escape_html($name) . '" displayname="" />';
                }
            } else {
                $likea = $GLOBALS['FORUM_DRIVER']->get_matching_members($id . '%', 15);
                if ((count($likea) == 15) && (addon_installed('chat')) && (!is_guest())) {
                    $likea = $GLOBALS['FORUM_DRIVER']->get_matching_members($id . '%', 15, true);
                } // Limit to friends, if possible

                foreach ($likea as $l) {
                    if (count($names) < 15) {
                        $names[$GLOBALS['FORUM_DRIVER']->mrow_id($l)] = $GLOBALS['FORUM_DRIVER']->mrow_username($l);
                    }
                }

                foreach ($names as $member_id => $name) {
                    echo '<option value="' . escape_html($name) . '" displayname="' . escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id, true)) . '" />';
                }
            }
        }

        sort($names);
        $names = array_unique($names);
    }

    echo '</result></request>';
}

/**
 * AJAX script for finding out privileges for the queried resource.
 */
function find_permissions_script()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/plain');

    require_code('permissions2');

    $serverid = get_param('serverid');
    $x = get_param('x');
    $matches = array();
    preg_match('#^access_(\d+)_privilege_(.+)$#', $x, $matches);
    $group_id = intval($matches[1]);
    $privilege = $matches[2];
    require_all_lang();
    echo do_lang('PRIVILEGE_' . $privilege) . '=';
    if ($serverid == '_root') {
        echo has_privilege_group($group_id, $privilege) ? do_lang('YES') : do_lang('NO');
    } else {
        require_code('sitemap');

        $test = find_sitemap_object($serverid);
        if (!is_null($test)) {
            list($ob,) = $test;

            $privilege_page = $ob->get_privilege_page($serverid);
        } else {
            $privilege_page = '';
        }

        echo has_privilege_group($group_id, $privilege, $privilege_page) ? do_lang('YES') : do_lang('NO');
    }
}

/**
 * AJAX script to store an autosave.
 */
function store_autosave()
{
    prepare_for_known_ajax_response();

    $member_id = get_member();
    $key = post_param('key');
    $value = post_param('value');
    $time = time();

    $GLOBALS['SITE_DB']->query_insert('autosave', array( // Will duplicate against a_member_id/a_key, but DB space is not an issue - better to have the back-archive of it
        'a_member_id' => $member_id,
        'a_key' => $key,
        'a_value' => $value,
        'a_time' => $time,
    ));
}

/**
 * AJAX script to retrieve an autosave.
 */
function retrieve_autosave()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/plain');

    $member_id = get_member();
    $key = post_param('key');

    @ini_set('ocproducts.xss_detect', '0');

    echo $GLOBALS['SITE_DB']->query_select_value_if_there('autosave', 'a_value', array('a_member_id' => $member_id, 'a_key' => $key), 'ORDER BY a_time DESC');
}

/**
 * AJAX script to make a fractional edit to some data.
 */
function fractional_edit_script()
{
    prepare_for_known_ajax_response();

    header('Content-type: text/plain; charset=' . get_charset());

    $_POST['fractional_edit'] = '1'; // FUDGE

    $zone = get_param('zone');
    $page = get_param('page');

    global $SESSION_CONFIRMED_CACHE;
    if (($SESSION_CONFIRMED_CACHE == 0) && ($GLOBALS['SITE_DB']->query_select_value('zones', 'zone_require_session', array('zone_name' => $zone)) == 1)) {
        return;
    }

    if (!has_actual_page_access(get_member(), $page, $zone)) {
        access_denied('ZONE_ACCESS');
    }

    require_code('failure');
    global $WANT_TEXT_ERRORS;
    $WANT_TEXT_ERRORS = true;

    require_code('site');
    request_page($page, true);

    $supports_comcode = get_param_integer('supports_comcode', 0) == 1;
    $param_name = get_param('edit_param_name');
    if (isset($_POST[$param_name . '__altered_rendered_output'])) {
        $edited = $_POST[$param_name . '__altered_rendered_output'];
    } else {
        $edited = post_param($param_name);
        if ($supports_comcode) {
            $_edited = comcode_to_tempcode($edited, get_member());
            $edited = $_edited->evaluate();
        } else {
            $edited = escape_html($edited);
        }
    }
    @ini_set('ocproducts.xss_detect', '0');
    echo $edited;
}

/**
 * AJAX script to tell if data has been changed.
 */
function change_detection_script()
{
    prepare_for_known_ajax_response();

    header('Content-type: text/plain; charset=' . get_charset());

    $page = get_param('page');

    require_code('hooks/systems/change_detection/' . filter_naughty($page), true);

    $refresh_if_changed = either_param('refresh_if_changed');
    $object = object_factory('Hook_change_detection_' . $page);
    $result = $object->run($refresh_if_changed);
    echo $result ? '1' : '0';
}

/**
 * AJAX script for recording that something is currently being edited.
 */
function edit_ping_script()
{
    prepare_for_known_ajax_response();

    header('Content-type: text/plain; charset=' . get_charset());

    $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'edit_pings WHERE the_time<' . strval(time() - 200));

    $GLOBALS['SITE_DB']->query_delete('edit_pings', array(
        'the_page' => get_param('page'),
        'the_type' => get_param('type'),
        'the_id' => get_param('id', false, true),
        'the_member' => get_member()
    ));

    $GLOBALS['SITE_DB']->query_insert('edit_pings', array(
        'the_page' => get_param('page'),
        'the_type' => get_param('type'),
        'the_id' => get_param('id', false, true),
        'the_time' => time(),
        'the_member' => get_member()
    ));

    echo '1';
}

/**
 * AJAX script for HTML<>Comcode conversion.
 */
function comcode_convert_script()
{
    prepare_for_known_ajax_response();

    require_code('site');
    attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

    require_lang('comcode');

    $data = post_param('data', null, false, false);
    if (is_null($data)) {
        $title = get_screen_title('_COMCODE');
        $fields = new ocp_tempcode();
        require_code('form_templates');
        $fields->attach(form_input_huge(do_lang_tempcode('TEXT'), '', 'data', '', true));
        $fields->attach(form_input_tick('Convert HTML to Comcode', '', 'from_html', false));
        $fields->attach(form_input_tick('Convert to semihtml', '', 'semihtml', false));
        $fields->attach(form_input_tick('Comes from WYSIWYG', '', 'data__is_wysiwyg', false));
        $fields->attach(form_input_tick('Lax mode (less parse rules)', '', 'lax', false));
        $hidden = new ocp_tempcode();
        $out2 = globalise(do_template('FORM_SCREEN', array('_GUID' => 'dd82970fa1196132e07049871c51aab7', 'TITLE' => $title, 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => do_lang_tempcode('VIEW'), 'TEXT' => '', 'HIDDEN' => $hidden, 'URL' => find_script('comcode_convert', true), 'FIELDS' => $fields)), null, '', true);
        $out2->evaluate_echo();
        return;
    }
    if (either_param_integer('from_html', 0) == 1) {
        require_code('comcode_from_html');
        $out = trim(semihtml_to_comcode($data));
    } else {
        if (either_param_integer('lax', 0) == 1) {
            $GLOBALS['LAX_COMCODE'] = true;
        }
        if (either_param_integer('is_semihtml', 0) == 1) {
            require_code('comcode_from_html');
            $data = semihtml_to_comcode($data);
        }
        $db = $GLOBALS['SITE_DB'];
        if (get_param_integer('forum_db', 0) == 1) {
            $db = $GLOBALS['FORUM_DB'];
        }
        $tpl = comcode_to_tempcode($data, get_member(), false, 60, null, $db, either_param_integer('semihtml', 0) == 1/*true*/, false, false, false);
        $evaluated = $tpl->evaluate();
        $out = '';
        if ($evaluated != '') {
            if (get_param_integer('css', 0) == 1) {
                global $CSSS;
                unset($CSSS['global']);
                unset($CSSS['no_cache']);
                $out .= static_evaluate_tempcode(css_tempcode());
            }
            if (get_param_integer('javascript', 0) == 1) {
                global $JAVASCRIPTS;
                unset($JAVASCRIPTS['javascript']);
                unset($JAVASCRIPTS['javascript_staff']);
                $out .= static_evaluate_tempcode(javascript_tempcode());
            }
        }
        $out .= trim(trim($evaluated));
    }

    if (either_param_integer('fix_bad_html', 0) == 1) {
        require_code('xhtml');
        $new = xhtmlise_html($out, true);

        $stripped_new = preg_replace('#<!--.*-->#Us', '', preg_replace('#\s+#', '', $new));
        $stripped_old = preg_replace('#<!--.*-->#Us', '', preg_replace('#\s+#', '', $out));
        if ($stripped_new != $stripped_old) {
            /*$myfile=fopen(get_file_base().'/a','wb'); // Useful for debugging
            fwrite($myfile,preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#',"\n",$new)));
            fclose($myfile);

            $myfile=fopen(get_file_base().'/b','wb');
            fwrite($myfile,preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#',"\n",$out)));
            fclose($myfile);*/

            $out = $new . do_lang('BROKEN_XHTML_FIXED');
        }
    }
    if (either_param_integer('keep_skip_rubbish', 0) == 0) {
        require_code('xml');

        @ini_set('ocproducts.xss_detect', '0');

        $box_title = get_param('box_title', '');
        if (is_object($out)) {
            $out = $out->evaluate();
        }
        if (($box_title != '') && ($out != '')) {
            $out = static_evaluate_tempcode(put_in_standard_box(make_string_tempcode($out), $box_title));
        }

        header('Content-Type: text/xml');
        echo '<?xml version="1.0" encoding="' . get_charset() . '"?' . '>';
        echo '<request><result>';
        echo xmlentities($out);
        echo '</result></request>';
    } else {
        @ini_set('ocproducts.xss_detect', '0');

        header('Content-type: text/plain; charset=' . get_charset());
        echo $out;
    }
}

/**
 * AJAX script for dynamically extended selection tree.
 */
function ajax_tree_script()
{
    // Closed site
    $site_closed = get_option('site_closed');
    if (($site_closed == '1') && (!has_privilege(get_member(), 'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN'])) {
        header('Content-Type: text/plain');
        @exit(get_option('closed'));
    }

    prepare_for_known_ajax_response();

    // NB: We use ajax_tree hooks to power this. Those hooks may or may not use the Sitemap API to get the tree structure. However, the default ones are hard-coded, for better performance.

    require_code('xml');
    header('Content-Type: text/xml');
    $hook = filter_naughty_harsh(get_param('hook'));
    require_code('hooks/systems/ajax_tree/' . $hook);
    $object = object_factory('Hook_' . $hook);
    $id = get_param('id', '', true);
    if ($id == '') {
        $id = null;
    }
    @ini_set('ocproducts.xss_detect', '0');
    $html_mask = get_param_integer('html_mask', 0) == 1;
    if (!$html_mask) {
        echo '<?xml version="1.0" encoding="' . get_charset() . '"?' . '>';
    }
    echo($html_mask ? '<html>' : '<request>');
    $_options = get_param('options', '', true);
    if ($_options == '') {
        $_options = serialize(array());
    }
    secure_serialized_data($_options);
    $options = @unserialize($_options);
    if ($options === false) {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }
    $val = $object->run($id, $options, get_param('default', null, true));
    echo str_replace('</body>', '<br id="ended" /></body>', $val);
    echo($html_mask ? '</html>' : '</request>');
}

/**
 * AJAX script for confirming a session is active.
 */
function confirm_session_script()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/plain');
    global $SESSION_CONFIRMED_CACHE;
    if ($SESSION_CONFIRMED_CACHE == 0) {
        echo $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    }
    echo '';
}

/**
 * AJAX script for getting the text of a template, as used by a certain theme.
 */
function load_template_script()
{
    prepare_for_known_ajax_response();

    if (!has_actual_page_access(get_member(), 'admin_themes', 'adminzone')) {
        exit();
    }

    @ini_set('ocproducts.xss_detect', '0');

    $theme = filter_naughty(get_param('theme'));
    $id = filter_naughty(get_param('id'));

    $x = get_custom_file_base() . '/themes/' . $theme . '/templates_custom/' . $id;
    if (!file_exists($x)) {
        $x = get_file_base() . '/themes/' . $theme . '/templates/' . $id;
    }
    if (!file_exists($x)) {
        $x = get_custom_file_base() . '/themes/default/templates_custom/' . $id;
    }
    if (!file_exists($x)) {
        $x = get_file_base() . '/themes/default/templates/' . $id;
    }
    if (file_exists($x)) {
        echo file_get_contents($x);
    }
}

/**
 * AJAX script for dynamic inclusion of CSS.
 */
function sheet_script()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/css');
    $sheet = get_param('sheet');
    if ($sheet != '') {
        echo str_replace('../../../', '', file_get_contents(css_enforce(filter_naughty_harsh($sheet))));
    }
}

/**
 * AJAX script for dynamic inclusion of XHTML snippets.
 */
function snippet_script()
{
    prepare_for_known_ajax_response();

    header('Content-Type: text/plain; charset=' . get_charset());
    $hook = filter_naughty_harsh(get_param('snippet'));
    require_code('hooks/systems/snippets/' . $hook, true);
    $object = object_factory('Hook_' . $hook);
    $tempcode = $object->run();
    $tempcode->handle_symbol_preprocessing();
    $out = $tempcode->evaluate();

    if ((strpos($out, "\n") !== false) && (strpos($hook, '__text') !== false)) { // Is HTML
        if ((!function_exists('simplexml_load_string')) || ((function_exists('simplexml_load_string')) && (@simplexml_load_string('<wrap>' . preg_replace('#&\w+;#', '', $out) . '</wrap>') === false))) { // Optimisation-- check first via optimised native PHP function if possible
            require_code('xhtml');
            $out = xhtmlise_html($out, true);
        }
    }

    // End early execution listening (this means register_shutdown_function will run after connection closed - faster)
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', 'Off');
    $size = strlen($out);
    header('Connection: close');
    @ignore_user_abort(true);
    header('Content-Encoding: none');
    header('Content-Length: ' . strval($size));
    echo $out;
    @ob_end_flush();
    flush();
}
