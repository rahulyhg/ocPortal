<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_ocf
 */

/**
 * Validate a post.
 *
 * @param  AUTO_LINK                    $post_id The ID of the post.
 * @param  ?AUTO_LINK                   $topic_id The ID of the topic that contains the post (null: find out from the DB).
 * @param  ?AUTO_LINK                   $forum_id The forum that the topic containing the post is in (null: find out from the DB).
 * @param  ?MEMBER                      $poster The member that made the post being validated (null: find out from the DB).
 * @param  ?LONG_TEXT                   $post The post, in Comcode format (null: It'll have to be looked-up).
 * @return AUTO_LINK                    The ID of the topic (whilst this could be known without calling this function, as we've gone to effort and grabbed it from the DB, it might turn out useful for something).
 */
function ocf_validate_post($post_id, $topic_id = null, $forum_id = null, $poster = null, $post = null)
{
    require_code('submit');
    send_content_validated_notification('post', strval($post_id));

    if (is_null($topic_id)) {
        $post_info = $GLOBALS['FORUM_DB']->query_select('f_posts', array('*'), array('id' => $post_id), '', 1);
        $topic_id = $post_info[0]['p_topic_id'];
        $forum_id = $post_info[0]['p_cache_forum_id'];
        $poster = $post_info[0]['p_poster'];
        $post = get_translated_text($post_info[0]['p_post'], $GLOBALS['FORUM_DB']);
    }

    if (!ocf_may_moderate_forum($forum_id)) {
        access_denied('I_ERROR');
    }

    $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_cache_first_post_id', 't_pt_from', 't_cache_first_title'), array('id' => $topic_id), '', 1);

    $GLOBALS['FORUM_DB']->query_update('f_posts', array(
        'p_validated' => 1,
    ), array('id' => $post_id), '', 1);

    if (!array_key_exists(0, $topic_info)) {
        return $topic_id; // Dodgy, topics gone missing
    }
    $is_starter = ($topic_info[0]['t_cache_first_post_id'] == $post_id);

    $GLOBALS['FORUM_DB']->query_update('f_topics', array( // Validating a post will also validate a topic
        't_validated' => 1,
    ), array('id' => $topic_id), '', 1);

    $_url = build_url(array('page' => 'topicview', 'id' => $topic_id), 'forum', null, false, false, true, 'post_' . strval($post_id));
    $url = $_url->evaluate();

    if (!is_null($forum_id)) {
        $post_counts = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'f_post_count_increment', array('id' => $forum_id));
        if ($post_counts === 1) {
            ocf_force_update_member_post_count($poster, 1);
            require_code('ocf_posts_action2');
            ocf_member_handle_promotion($poster);
        }
    }

    ocf_send_topic_notification($url, $topic_id, $forum_id, $poster, $is_starter, $post, $topic_info[0]['t_cache_first_title'], null, !is_null($topic_info[0]['t_pt_from']));

    if (!is_null($forum_id)) {
        ocf_force_update_forum_cacheing($forum_id, 0, 1);
    }

    ocf_force_update_topic_cacheing($topic_id, 1, true, true);

    return $topic_id; // Because we might want this
}

/**
 * Edit a post.
 *
 * @param  AUTO_LINK                    $post_id The ID of the post that we're editing.
 * @param  BINARY                       $validated Whether the post is validated.
 * @param  SHORT_TEXT                   $title The title of the post (may be blank).
 * @param  LONG_TEXT                    $post The post.
 * @param  BINARY                       $skip_sig Whether to skip showing the posters signature in the post.
 * @param  BINARY                       $is_emphasised Whether the post is marked emphasised.
 * @param  ?MEMBER                      $intended_solely_for The member that this post is intended solely for (null: none).
 * @param  boolean                      $show_as_edited Whether to show the post as edited.
 * @param  boolean                      $mark_as_unread Whether to mark the topic as unread by those previous having read this post.
 * @param  LONG_TEXT                    $reason The reason for this action.
 * @param  boolean                      $check_perms Whether to check permissions.
 * @param  ?TIME                        $edit_time Edit time (null: either means current time, or if $null_is_literal, means reset to to NULL)
 * @param  ?TIME                        $add_time Add time (null: do not change)
 * @param  ?MEMBER                      $submitter Submitter (null: do not change)
 * @param  boolean                      $null_is_literal Determines whether some NULLs passed mean 'use a default' or literally mean 'set to NULL'
 * @param  boolean                      $run_checks Whether to run checks
 * @return AUTO_LINK                    The ID of the topic (whilst this could be known without calling this function, as we've gone to effort and grabbed it from the DB, it might turn out useful for something).
 */
function ocf_edit_post($post_id, $validated, $title, $post, $skip_sig, $is_emphasised, $intended_solely_for, $show_as_edited, $mark_as_unread, $reason, $check_perms = true, $edit_time = null, $add_time = null, $submitter = null, $null_is_literal = false, $run_checks = true)
{
    if (is_null($edit_time)) {
        $edit_time = $null_is_literal ? null : time();
    }

    $post_info = $GLOBALS['FORUM_DB']->query_select('f_posts', array('p_topic_id', 'p_time', 'p_post', 'p_poster', 'p_cache_forum_id'), array('id' => $post_id));
    if (!array_key_exists(0, $post_info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $_postdetails = $post_info[0]['p_post'];
    $post_owner = $post_info[0]['p_poster'];
    $forum_id = $post_info[0]['p_cache_forum_id'];
    $topic_id = $post_info[0]['p_topic_id'];
    $update_map = array();

    require_code('ocf_posts_action');
    require_code('ocf_posts');
    if ($run_checks) {
        ocf_check_post($post);
    }

    if ($check_perms) {
        $closed = ($GLOBALS['FORUM_DB']->query_select_value('f_topics', 't_is_open', array('id' => $topic_id)) == 0);
        if (!ocf_may_edit_post_by($post_id, $post_info[0]['p_time'], $post_owner, $forum_id, null, $closed)) {
            access_denied('I_ERROR');
        }
    }
    if ((is_null($validated)) || ($validated == 1)) {
        if ((!is_null($forum_id)) && (!has_privilege(get_member(), 'bypass_validation_lowrange_content', 'topics', array('forums', $forum_id)))) {
            $validated = 0;
        } else {
            $validated = 1;
        }
        if (($mark_as_unread)/* && (ocf_may_moderate_forum($forum_id))*/) {
            $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_cache_last_time' => time(), 't_cache_last_post_id' => $post_id, 't_cache_last_title' => $title, 't_cache_last_username' => $GLOBALS['FORUM_DRIVER']->get_username($post_owner, true), 't_cache_last_member_id' => $post_owner), array('id' => $topic_id), '', 1);

            $GLOBALS['FORUM_DB']->query_delete('f_read_logs', array('l_topic_id' => $topic_id));
        }
    }

    // Save in history
    $ticket_forum = get_option('ticket_forum_name', true);
    if ((is_null($ticket_forum)) || ($forum_id != $GLOBALS['FORUM_DRIVER']->forum_id_from_name($ticket_forum))) {
        $GLOBALS['FORUM_DB']->query_insert('f_post_history', array(
            'h_create_date_and_time' => $post_info[0]['p_time'],
            'h_action_date_and_time' => is_null($edit_time) ? time() : $edit_time,
            'h_owner_member_id' => $post_owner,
            'h_alterer_member_id' => get_member(),
            'h_post_id' => $post_id,
            'h_topic_id' => $topic_id,
            'h_before' => get_translated_text($_postdetails, $GLOBALS['FORUM_DB']),
            'h_action' => 'EDIT_POST'
        ));
    }

    require_code('attachments2');
    require_code('attachments3');
    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }
    $update_map += array(
        'p_title' => $title,
        'p_is_emphasised' => $is_emphasised,
        'p_intended_solely_for' => $intended_solely_for,
        'p_validated' => $validated,
        'p_skip_sig' => $skip_sig,
    );
    $update_map += update_lang_comcode_attachments('p_post', $_postdetails, $post, 'ocf_post', strval($post_id), $GLOBALS['FORUM_DB'], false, $post_owner);

    if ($show_as_edited) {
        $update_map['p_last_edit_time'] = $edit_time;
        $update_map['p_last_edit_by'] = get_member();
    } else {
        $update_map['p_last_edit_time'] = null;
        $update_map['p_last_edit_by'] = null;
    }

    if (!is_null($add_time)) {
        $update_map['p_time'] = $add_time;
    }
    if (!is_null($submitter)) {
        $update_map['p_poster'] = $submitter;
    }

    $GLOBALS['FORUM_DB']->query_update('f_posts', $update_map, array('id' => $post_id), '', 1);

    // Update topic cacheing
    $info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_cache_first_post_id', 't_cache_first_title'), array('id' => $topic_id), '', 1);
    if ((array_key_exists(0, $info)) && ($info[0]['t_cache_first_post_id'] == $post_id) && ($info[0]['t_cache_first_title'] != $title)) {
        require_code('urls2');
        suggest_new_idmoniker_for('topicview', 'browse', strval($topic_id), '', $title);

        $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_cache_first_title' => $title), array('id' => $topic_id), '', 1);
    }

    require_code('ocf_general_action2');
    ocf_mod_log_it('EDIT_POST', strval($post_id), $title, $reason);

    if (!is_null($forum_id)) {
        ocf_decache_ocp_blocks($forum_id);
    }

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('post', strval($post_id));
    }

    return $topic_id; // We may want this
}

/**
 * Delete posts from a topic.
 *
 * @param  AUTO_LINK                    $topic_id The ID of the topic we're deleting posts from.
 * @param  array                        $posts A list of posts to delete.
 * @param  LONG_TEXT                    $reason The reason for this action.
 * @param  boolean                      $check_perms Whether to check permissions.
 * @param  boolean                      $cleanup Whether to do a cleanup: delete the topic if there will be no posts left in it.
 * @return boolean                      Whether the topic was deleted, due to all posts in said topic being deleted.
 */
function ocf_delete_posts_topic($topic_id, $posts, $reason = '', $check_perms = true, $cleanup = true)
{
    // Info about source
    $info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_forum_id', 't_pt_from', 't_pt_to'), array('id' => $topic_id), '', 1);
    if (!array_key_exists(0, $info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $forum_id = $info[0]['t_forum_id'];

    $or_list = '';
    foreach ($posts as $post) {
        if ($or_list != '') {
            $or_list .= ' OR ';
        }

        $or_list .= 'id=' . strval($post);

        if (addon_installed('catalogues')) {
            update_catalogue_content_ref('post', strval($post), '');
        }
    }

    // Check access
    $_postdetails = $GLOBALS['FORUM_DB']->query('SELECT id,p_post,p_poster,p_time,p_intended_solely_for,p_validated FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE ' . $or_list, null, null, false, true);
    $num_posts_counted = 0;
    foreach ($_postdetails as $post) {
        if ((is_null($post['p_intended_solely_for'])) && ($post['p_validated'] == 1)) {
            $num_posts_counted++;
        }
        $post_owner = $post['p_poster'];
        if ($check_perms) {
            if (!ocf_may_delete_post_by($post['id'], $post['p_time'], $post_owner, $forum_id)) {
                access_denied('I_ERROR');
            }
        }
    }

    // Save in history
    foreach ($_postdetails as $post) {
        $GLOBALS['FORUM_DB']->query_insert('f_post_history', array(
            'h_create_date_and_time' => $post['p_time'],
            'h_action_date_and_time' => time(),
            'h_owner_member_id' => $post['p_poster'],
            'h_alterer_member_id' => get_member(),
            'h_post_id' => $post['id'],
            'h_topic_id' => $topic_id,
            'h_before' => get_translated_text($post['p_post'], $GLOBALS['FORUM_DB']),
            'h_action' => 'DELETE_POST'
        ));
    }

    // Update member post counts
    $post_counts = is_null($forum_id) ? 1 : $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_post_count_increment', array('id' => $forum_id));
    if ($post_counts == 1) {
        $_member_post_counts = $GLOBALS['FORUM_DB']->query('SELECT p_poster FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE ' . $or_list, null, null, false, true);
        $member_post_counts = array();
        foreach ($_member_post_counts as $_member_post_count) {
            $_member = $_member_post_count['p_poster'];
            if (!array_key_exists($_member, $member_post_counts)) {
                $member_post_counts[$_member] = 0;
            }
            $member_post_counts[$_member]++;
        }

        foreach ($member_post_counts as $member_id => $member_post_count) {
            if (!is_null($forum_id)) {
                ocf_force_update_member_post_count($member_id, -$member_post_count);
            }
        }
    }

    // Clean up lang
    require_code('attachments2');
    require_code('attachments3');
    foreach ($_postdetails as $post) {
        delete_lang_comcode_attachments($post['p_post'], 'ocf_post', $post['id'], $GLOBALS['FORUM_DB']);
    }

    $GLOBALS['FORUM_DB']->query('DELETE FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE ' . $or_list, null, null, false, true);
    $GLOBALS['SITE_DB']->query('DELETE FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'review_supplement WHERE ' . str_replace('id=', 'r_post_id=', $or_list), null, null, false, true);

    $test = $GLOBALS['FORUM_DB']->query_select_value('f_posts', 'COUNT(*)', array('p_topic_id' => $topic_id));
    if (($test == 0) && ($cleanup)) {
        require_code('ocf_topics_action');
        require_code('ocf_topics_action2');
        ocf_delete_topic($topic_id, do_lang('DELETE_POSTS'));
        $ret = true;
    } else {
        $ret = false;

        // Update cacheing
        ocf_force_update_topic_cacheing($topic_id, -$num_posts_counted, true, true);
    }
    if (!is_null($forum_id)) {
        ocf_force_update_forum_cacheing($forum_id, 0, -$num_posts_counted);
    }

    require_code('ocf_general_action2');
    if (count($posts) == 1) {
        ocf_mod_log_it('DELETE_POST', strval($topic_id), strval($posts[0]), $reason);
    } else {
        ocf_mod_log_it('DELETE_POSTS', strval($topic_id), strval(count($posts)), $reason);
    }

    if (!is_null($forum_id)) {
        ocf_decache_ocp_blocks($forum_id);
    } else {
        decache('side_ocf_private_topics', null, $info[0]['t_pt_from']);
        decache('_new_pp', null, $info[0]['t_pt_from']);
        decache('_get_pts', null, $info[0]['t_pt_from']);
        decache('side_ocf_private_topics', null, $info[0]['t_pt_to']);
        decache('_new_pp', null, $info[0]['t_pt_to']);
        decache('_get_pts', null, $info[0]['t_pt_to']);
    }

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        foreach ($posts as $post) {
            expunge_resourcefs_moniker('post', strval($post));
        }
    }

    return $ret;
}

/**
 * Move posts from one topic to another.
 *
 * @param  AUTO_LINK                    $from_topic_id The ID of the source topic.
 * @param  AUTO_LINK                    $to_topic_id The ID of the destination topic.
 * @param  array                        $posts A list of post IDs to move.
 * @param  LONG_TEXT                    $reason The reason for this action.
 * @param  ?AUTO_LINK                   $to_forum_id The forum the destination topic is in (null: find from DB).
 * @param  boolean                      $delete_if_empty Whether to delete the topic if all posts in it have been moved.
 * @param  ?SHORT_TEXT                  $title The title for the new topic (null: work out / irrelevant).
 * @return boolean                      Whether the topic was deleted.
 */
function ocf_move_posts($from_topic_id, $to_topic_id, $posts, $reason, $to_forum_id = null, $delete_if_empty = false, $title = null)
{
    // Info about source
    $from_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_forum_id'), array('id' => $from_topic_id));
    if (!array_key_exists(0, $from_info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $from_forum_id = $from_info[0]['t_forum_id'];

    // Useful for queries
    $or_list = '';
    foreach ($posts as $post) {
        if ($or_list != '') {
            $or_list .= ' OR ';
        }
        $or_list .= 'id=' . strval($post);
    }

    // Is it a support ticket move?
    if (addon_installed('tickets')) {
        require_code('tickets');
        $is_support_ticket = (is_ticket_forum($from_forum_id)) && (is_ticket_forum($to_forum_id));
    } else {
        $is_support_ticket = false;
    }

    // Create topic, if this is a split
    if (is_null($to_topic_id)) {
        if (is_null($to_forum_id)) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        if ($is_support_ticket) {
            // For support tickets, we need to make the spacer post
            require_code('tickets2');
            $member = get_member();
            foreach ($posts as $post) {
                $member = $GLOBALS['FORUM_DB']->query_select_value('f_posts', 'p_poster', array('id' => $posts[0]));
                if ($member != get_member()) {
                    break;
                }
            }
            $ticket_id = strval($member) . '_' . uniqid('', true);
            $ticket_type = $GLOBALS['FORUM_DB']->query_select_value('tickets', 'ticket_type', array('topic_id' => $from_topic_id));
            if (is_null($title)) {
                $title = $GLOBALS['FORUM_DB']->query_select_value('f_posts', 'p_title', array('id' => $posts[0]));
            }
            $_ticket_url = build_url(array('page' => 'tickets', 'type' => 'ticket', 'id' => $ticket_id, 'redirect' => null), get_module_zone('tickets'), null, false, true, true);
            $ticket_url = $_ticket_url->evaluate();
            ticket_add_post($member, $ticket_id, $ticket_type, $title, '', $ticket_url);
            $to_topic_id = $GLOBALS['LAST_TOPIC_ID'];
            $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts SET p_time=' . strval(time()) . ' WHERE ' . $or_list, null, null, false, true);
        } else {
            require_code('ocf_topics_action');
            $to_topic_id = ocf_make_topic($to_forum_id);
        }

        if ((!is_null($title)) && (count($posts) != 0)) {
            $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_title' => $title), array('id' => $posts[0]), '', 1);
        }
    }

    // Info about destination
    $to_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_forum_id'), array('id' => $to_topic_id));
    if (!array_key_exists(0, $to_info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $to_forum_id = $to_info[0]['t_forum_id'];

    // Check access
    if (!ocf_may_moderate_forum($from_forum_id)) {
        access_denied('I_ERROR');
    }
    $_postdetails = $GLOBALS['FORUM_DB']->query('SELECT p_cache_forum_id,p_intended_solely_for,p_validated FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE ' . $or_list, null, null, false, true);
    $num_posts_counted = 0;
    foreach ($_postdetails as $post) {
        if ((is_null($post['p_intended_solely_for'])) && ($post['p_validated'] == 1)) {
            $num_posts_counted++;
        }
        if ($post['p_cache_forum_id'] != $from_forum_id) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
    }

    // Update cacheing
    $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts SET p_cache_forum_id=' . strval($to_forum_id) . ', p_topic_id=' . strval($to_topic_id) . ' WHERE ' . $or_list, null, null, false, true);
    require_code('ocf_posts_action2');
    ocf_force_update_topic_cacheing($from_topic_id, -$num_posts_counted, true, true);
    ocf_force_update_topic_cacheing($to_topic_id, $num_posts_counted, true, true);
    if ((!is_null($from_forum_id)) && (!is_null($to_topic_id)) && ($from_forum_id != $to_topic_id)) {
        if ($from_forum_id != $to_forum_id) {
            require_code('ocf_forums_action2');
            ocf_force_update_forum_cacheing($from_forum_id, 0, -$num_posts_counted);
            ocf_force_update_forum_cacheing($to_forum_id, 0, $num_posts_counted);

            // Update member post counts if we've switched between post-count countable forums
            $post_count_info = $GLOBALS['FORUM_DB']->query('SELECT id,f_post_count_increment FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($from_forum_id) . ' OR id=' . strval($to_forum_id), 2);
            if ($post_count_info[0]['id'] == $from_forum_id) {
                $from = $post_count_info[0]['f_post_count_increment'];
                $to = $post_count_info[1]['f_post_count_increment'];
            } else {
                $from = $post_count_info[1]['f_post_count_increment'];
                $to = $post_count_info[0]['f_post_count_increment'];
            }
            if ($from != $to) {
                $_member_post_counts = collapse_1d_complexity('p_poster', $GLOBALS['FORUM_DB']->query('SELECT p_poster FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE ' . $or_list, null, null, false, true));
                $member_post_counts = array_count_values($_member_post_counts);

                foreach ($member_post_counts as $member_id => $member_post_count) {
                    if ($to == 0) {
                        $member_post_count = -$member_post_count;
                    }
                    ocf_force_update_member_post_count($member_id, $member_post_count);
                }
            }
        }
    }

    // Delete if needed
    $test = $delete_if_empty ? $GLOBALS['FORUM_DB']->query_select_value('f_posts', 'COUNT(*)', array('p_topic_id' => $from_topic_id)) : 1;
    if ($test == 0) {
        $num_view_count = 0;
        $num_view_count += $GLOBALS['FORUM_DB']->query_select_value('f_topics', 't_num_views', array('id' => $from_topic_id));
        $num_view_count += $GLOBALS['FORUM_DB']->query_select_value('f_topics', 't_num_views', array('id' => $to_topic_id));
        $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_num_views' => $num_view_count), array('id' => $to_topic_id), '', 1);

        require_code('ocf_topics_action');
        require_code('ocf_topics_action2');
        ocf_delete_topic($from_topic_id, do_lang('MOVE_POSTS'));
        return true;
    } else {
        // Make informative post
        $topic_title = $GLOBALS['FORUM_DB']->query_select_value('f_topics', 't_cache_first_title', array('id' => $to_topic_id));
        if ($is_support_ticket) {
            $to_link = '[page="' . get_module_zone('tickets') . ':tickets:ticket:' . $ticket_id . '"]' . str_replace('"', '\"', str_replace('[', '\\[', $topic_title)) . '[/page]';
        } else {
            $to_link = '[page="' . get_module_zone('topicview') . ':topicview:browse:' . strval($to_topic_id) . '"]' . str_replace('"', '\"', str_replace('[', '\\[', $topic_title)) . '[/page]';
        }
        $me_link = '[page="' . get_module_zone('members') . ':members:view:' . strval(get_member()) . '"]' . $GLOBALS['OCF_DRIVER']->get_username(get_member(), true) . '[/page]';
        $lang = do_lang('INLINE_POSTS_MOVED_MESSAGE', $me_link, integer_format(count($posts)), array($to_link, get_timezoned_date(time())));
        ocf_make_post($from_topic_id, '', $lang, 0, false, 1, 1, null, null, $GLOBALS['FORUM_DB']->query_select_value('f_posts', 'p_time', array('id' => $posts[0])) + 1, null, null, null, null, false);

        require_code('ocf_general_action2');
        ocf_mod_log_it('MOVE_POSTS', strval($to_topic_id), strval(count($posts)), $reason);

        if (!is_null($from_forum_id)) {
            ocf_decache_ocp_blocks($from_forum_id);
        }
        if (!is_null($to_forum_id)) {
            ocf_decache_ocp_blocks($to_forum_id);
        }

        return false;
    }
}
