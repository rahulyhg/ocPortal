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
 * @package    import
 */

require_code('hooks/modules/admin_import/shared/ipb');

/**
 * Hook class.
 */
class Hook_ipb1 extends Hook_ipb_base
{
    /**
     * Standard importer hook info function.
     *
     * @return ?array                   Importer handling details, including lists of all the import types covered (import types are not necessarily the same as actual tables) (null: importer is disabled).
     */
    public function info()
    {
        $info = array();
        $info['supports_advanced_import'] = false;
        $info['product'] = 'Invision Board 1.3.x';
        $info['prefix'] = 'ibf_';
        $info['import'] = array(
            'ocf_groups',
            'ocf_members',
            'ocf_member_files',
            'ocf_custom_profile_fields',
            'ocf_forum_groupings',
            'ocf_forums',
            'ocf_topics',
            'ocf_posts',
            'ocf_post_files',
            'ocf_polls_and_votes',
            'ocf_multi_moderations',
            'notifications',
            'ocf_private_topics',
            'ocf_warnings',
            'wordfilter',
            'config',
            'calendar',
        );
        $info['dependencies'] = array( // This dependency tree is overdefined, but I wanted to make it clear what depends on what, rather than having a simplified version
            'ocf_members' => array('ocf_groups'),
            'ocf_member_files' => array('ocf_members'),
            'ocf_forums' => array('ocf_forum_groupings', 'ocf_members', 'ocf_groups'),
            'ocf_topics' => array('ocf_forums', 'ocf_members'),
            'ocf_polls_and_votes' => array('ocf_topics', 'ocf_members'),
            'ocf_posts' => array('ocf_topics', 'ocf_members'),
            'ocf_post_files' => array('ocf_posts'),
            'ocf_multi_moderations' => array('ocf_forums'),
            'notifications' => array('ocf_topics', 'ocf_members'),
            'ocf_private_topics' => array('ocf_members'),
            'ocf_warnings' => array('ocf_members'),
            'calendar' => array('ocf_members'),
        );
        $_cleanup_url = build_url(array('page' => 'admin_cleanup'), get_module_zone('admin_cleanup'));
        $cleanup_url = $_cleanup_url->evaluate();
        $info['message'] = (get_param('type', 'browse') != 'import' && get_param('type', 'browse') != 'hook') ? new Tempcode() : do_lang_tempcode('FORUM_CACHE_CLEAR', escape_html($cleanup_url));

        return $info;
    }

    /**
     * Standard import function.
     *
     * @param  object                   $db The DB connection to import from
     * @param  string                   $table_prefix The table prefix the target prefix is using
     * @param  PATH                     $old_base_dir The base directory we are importing from
     */
    public function import_ocf_forum_groupings($db, $table_prefix, $old_base_dir)
    {
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'categories');
        foreach ($rows as $row) {
            if (import_check_if_imported('category', strval($row['id']))) {
                continue;
            }

            if ($row['id'] == -1) {
                continue;
            }

            $title = @html_entity_decode($row['name'], ENT_QUOTES, get_charset());

            $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings', 'id', array('c_title' => $title));
            if (!is_null($test)) {
                import_id_remap_put('category', strval($row['id']), $test);
                continue;
            }

            $description = strip_tags(@html_entity_decode($row['description'], ENT_QUOTES, get_charset()));
            $expanded_by_default = 1;

            $id_new = ocf_make_forum_grouping($title, $description, $expanded_by_default);

            import_id_remap_put('category', strval($row['id']), $id_new);
        }
    }

    /**
     * Standard import function.
     *
     * @param  object                   $db The DB connection to import from
     * @param  string                   $table_prefix The table prefix the target prefix is using
     * @param  PATH                     $old_base_dir The base directory we are importing from
     */
    public function import_ocf_forums($db, $table_prefix, $old_base_dir)
    {
        require_code('ocf_forums_action2');

        $remap_id = array();
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'forums');
        foreach ($rows as $row) {
            $remapped = import_id_remap_get('forum', strval($row['id']), true);
            if (!is_null($remapped)) {
                $remap_id[$row['id']] = $remapped;
                continue;
            }

            if ($row['id'] == -1) {
                continue;
            }

            $name = @html_entity_decode($row['name'], ENT_QUOTES, get_charset());
            $description = strip_tags(@html_entity_decode($row['description'], ENT_QUOTES, get_charset()));
            $category_id = import_id_remap_get('category', strval($row['category']));
            $parent_forum = db_get_first_id();
            $position = $row['position'];
            $post_count_increment = $row['inc_postcount'];

            $_all_groups = array_unique(explode(',', $row['start_perms'] . ',' . $row['reply_perms'] . ',' . $row['read_perms']));
            $level2_groups = explode(',', $row['read_perms']);
            $level3_groups = explode(',', $row['reply_perms']);
            $level4_groups = explode(',', $row['start_perms']);
            $access_mapping = array();
            foreach ($_all_groups as $old_group) {
                $new_group = import_id_remap_get('group', strval($old_group), true);
                if (is_null($new_group)) {
                    continue;
                }

                if (in_array($old_group, $level4_groups)) {
                    $access_mapping[$new_group] = 4;
                } elseif (in_array($old_group, $level3_groups)) {
                    $access_mapping[$new_group] = 3;
                } elseif (in_array($old_group, $level2_groups)) {
                    $access_mapping[$new_group] = 2;
                } else {
                    $access_mapping[$new_group] = 0;
                }
            }

            $id_new = ocf_make_forum($name, $description, $category_id, $access_mapping, $parent_forum, $position, $post_count_increment);

            $remap_id[$row['id']] = $id_new;
            import_id_remap_put('forum', strval($row['id']), $id_new);
        }

        // Now we must fix parenting
        foreach ($rows as $row) {
            if (!((is_null($row['parent_id'])) || ($row['parent_id'] == -1))) {
                $parent_id = $remap_id[$row['parent_id']];
                $GLOBALS['FORUM_DB']->query_update('f_forums', array('f_parent_forum' => $parent_id), array('id' => $remap_id[$row['id']]), '', 1);
            }
        }
    }

    /**
     * Standard import function.
     *
     * @param  object                   $db The DB connection to import from
     * @param  string                   $table_prefix The table prefix the target prefix is using
     * @param  PATH                     $file_base The base directory we are importing from
     */
    public function import_config($db, $table_prefix, $file_base)
    {
        global $PROBED_FORUM_CONFIG;
        require($file_base . '/conf_global.php');
        set_option('staff_address', $PROBED_FORUM_CONFIG['email_out']);
        set_option('restricted_usernames', $PROBED_FORUM_CONFIG['ban_names']);
        /*set_option('forum_posts_per_page',$PROBED_FORUM_CONFIG['display_max_posts']);   Not useful
        set_option('forum_topics_per_page',$PROBED_FORUM_CONFIG['display_max_topics']);*/
        set_option('site_name', $PROBED_FORUM_CONFIG['home_name']);
        set_option('site_closed', $PROBED_FORUM_CONFIG['board_offline']);
        set_option('closed', $PROBED_FORUM_CONFIG['offline_msg']);
        set_option('session_expiry_time', strval(intval(round($PROBED_FORUM_CONFIG['session_expiration'] / 3600))));

        // Now some usergroup options
        list($width, $height) = explode('x', $PROBED_FORUM_CONFIG['avatar_dims']);
        $GLOBALS['FORUM_DB']->query_update('f_groups', array('g_max_avatar_width' => $width, 'g_max_avatar_height' => $height, 'g_max_sig_length_comcode' => $PROBED_FORUM_CONFIG['max_sig_length'], 'g_max_post_length_comcode' => $PROBED_FORUM_CONFIG['max_post_length']));
    }

    /**
     * Standard import function.
     *
     * @param  object                   $db The DB connection to import from
     * @param  string                   $table_prefix The table prefix the target prefix is using
     * @param  PATH                     $old_base_dir The base directory we are importing from
     */
    public function import_ocf_private_topics($db, $table_prefix, $old_base_dir)
    {
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'messages WHERE vid<>\'sent\' ORDER BY msg_date');

        // Group them up into what will become topics
        $groups = array();
        foreach ($rows as $row) {
            if ($row['from_id'] > $row['recipient_id']) {
                $a = $row['recipient_id'];
                $b = $row['from_id'];
            } else {
                $a = $row['from_id'];
                $b = $row['recipient_id'];
            }
            $title = str_replace('Re: ', '', $row['title']);
            $title = str_replace('RE: ', '', $title);
            $title = str_replace('Re:', '', $title);
            $title = str_replace('RE:', '', $title);
            $groups[strval($a) . ':' . strval($b) . ':' . @html_entity_decode($title, ENT_QUOTES, get_charset())][] = $row;
        }

        // Import topics
        foreach ($groups as $group) {
            $row = $group[0];

            if (import_check_if_imported('pt', strval($row['msg_id']))) {
                continue;
            }

            // Create topic
            $from_id = import_id_remap_get('member', strval($row['from_id']), true);
            if (is_null($from_id)) {
                $from_id = $GLOBALS['OCF_DRIVER']->get_guest_id();
            }
            $to_id = import_id_remap_get('member', strval($row['recipient_id']), true);
            if (is_null($to_id)) {
                $to_id = $GLOBALS['OCF_DRIVER']->get_guest_id();
            }
            $topic_id = ocf_make_topic(null, '', '', 1, 1, 0, 0, 0, $from_id, $to_id, false);

            $first_post = true;
            foreach ($group as $_postdetails) {
                if ($first_post) {
                    $title = @html_entity_decode($row['title'], ENT_QUOTES, get_charset());
                } else {
                    $title = '';
                }

                $post = $this->clean_ipb_post($_postdetails['message']);
                $validated = 1;
                $from_id = import_id_remap_get('member', strval($_postdetails['from_id']), true);
                if (is_null($from_id)) {
                    $from_id = $GLOBALS['OCF_DRIVER']->get_guest_id();
                }
                $poster_name_if_guest = $GLOBALS['OCF_DRIVER']->get_member_row_field($from_id, 'm_username');
                $ip_address = $GLOBALS['OCF_DRIVER']->get_member_row_field($from_id, 'm_ip_address');
                $time = $_postdetails['msg_date'];
                $poster = $from_id;
                $last_edit_time = null;
                $last_edit_by = null;

                ocf_make_post($topic_id, $title, $post, 0, $first_post, $validated, 0, $poster_name_if_guest, $ip_address, $time, $poster, null, $last_edit_time, $last_edit_by, false, false, null, false);
                $first_post = false;
            }

            import_id_remap_put('pt', strval($row['msg_id']), $topic_id);
        }
    }
}
