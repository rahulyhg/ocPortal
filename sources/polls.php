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
 * @package    polls
 */

/**
 * Show an actual poll box.
 *
 * @param  boolean                      Whether to show results (if we've already voted, this'll be overridden)
 * @param  array                        The poll row
 * @param  ID_TEXT                      The zone our poll module is in
 * @param  boolean                      Whether to include extra management links (e.g. editing, choosing, archive, etc)
 * @param  boolean                      Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  ID_TEXT                      Overridden GUID to send to templates (blank: none)
 * @return tempcode                     The box
 */
function render_poll_box($results, $myrow, $zone = '_SEARCH', $include_manage_links = false, $give_context = true, $guid = '')
{
    require_lang('polls');

    $just_poll_row = db_map_restrict($myrow, array('id', 'question', 'option1', 'option2', 'option3', 'option4', 'option5', 'option6', 'option7', 'option8', 'option9', 'option10'));

    $ip = get_ip_address();
    if (!may_vote_in_poll($myrow['id'], get_member(), get_ip_address())) {
        $results = true;
    }

    // Count our total votes
    $num_options = $myrow['num_options'];
    $totalvotes = 0;
    for ($i = 1; $i <= $num_options; $i++) {
        if (!array_key_exists('votes' . strval($i), $myrow)) {
            $myrow['votes' . strval($i)] = 0;
        }
        $totalvotes += $myrow['votes' . strval($i)];
    }

    // Sort by results
    $orderings = array();
    for ($i = 1; $i <= $num_options; $i++) {
        $orderings[$i] = $myrow['votes' . strval($i)];
    }
    if ($results) {
        asort($orderings);
    }

    $poll_results = 'show_poll_results_' . strval($myrow['id']);
    $vote_url = get_self_url(false, true, array('poll_id' => $myrow['id'], $poll_results => 1));
    $result_url = $results ? new ocp_tempcode() : get_self_url(false, true, array($poll_results => 1));

    // Our questions templated
    $tpl = new ocp_tempcode();
    for ($i = 1; $i <= $num_options; $i++) {
        $answer = get_translated_tempcode('poll', $just_poll_row, 'option' . strval($i));
        $answer_plain = get_translated_text($myrow['option' . strval($i)]);
        if (!$results) {
            $tpl->attach(do_template('POLL_ANSWER', array('_GUID' => ($guid != '') ? $guid : 'bc9c2e818f2e7031075d8d7b01d79cd5', 'PID' => strval($myrow['id']), 'I' => strval($i), 'CAST' => strval($i), 'VOTE_URL' => $vote_url, 'ANSWER' => $answer, 'ANSWER_PLAIN' => $answer_plain)));
        } else {
            $votes = $myrow['votes' . strval($i)];
            if (!is_numeric($votes)) {
                $votes = 0;
            }
            if ($totalvotes != 0) {
                $width = intval(round(70.0 * floatval($votes) / floatval($totalvotes)));
            } else {
                $width = 0;
            }
            $tpl->attach(do_template('POLL_ANSWER_RESULT', array('_GUID' => ($guid != '') ? $guid : '887ea0ed090c48305eb84500865e5178', 'PID' => strval($myrow['id']), 'I' => strval($i), 'VOTE_URL' => $vote_url, 'ANSWER' => $answer, 'ANSWER_PLAIN' => $answer_plain, 'WIDTH' => strval($width), 'VOTES' => integer_format($votes))));
        }
    }

    if ($include_manage_links) {
        if ((has_actual_page_access(null, 'cms_polls', null, null)) && (has_submit_permission('mid', get_member(), get_ip_address(), 'cms_polls'))) {
            $submit_url = build_url(array('page' => 'cms_polls', 'type' => 'ad', 'redirect' => get_self_url(true, true, array())), get_module_zone('cms_polls'));
        } else {
            $submit_url = new ocp_tempcode();
        }

        $archive_url = build_url(array('page' => 'polls', 'type' => 'misc'), $zone);
    } else {
        $submit_url = new ocp_tempcode();
        $archive_url = new ocp_tempcode();
    }

    // Do our final template
    $question = get_translated_tempcode('poll', $just_poll_row, 'question');
    $question_plain = get_translated_text($myrow['question']);
    $full_url = new ocp_tempcode();
    if ((get_page_name() != 'polls') || (get_param('type', '') != 'view')) {
        $full_url = build_url(array('page' => 'polls', 'type' => 'view', 'id' => $myrow['id']), $zone);
    }
    $map = array(
        '_GUID' => ($guid != '') ? $guid : '4c6b026f7ed96f0b5b8408eb5e5affb5',
        'VOTE_URL' => $vote_url,
        'SUBMITTER' => strval($myrow['submitter']),
        'PID' => strval($myrow['id']),
        'FULL_URL' => $full_url,
        'CONTENT' => $tpl,
        'QUESTION' => $question,
        'QUESTION_PLAIN' => $question_plain,
        'SUBMIT_URL' => $submit_url,
        'ARCHIVE_URL' => $archive_url,
        'RESULT_URL' => $result_url,
        'GIVE_CONTEXT' => $give_context,
    );
    if ((get_option('is_on_comments') == '1') && (!has_no_forum()) && ($myrow['allow_comments'] >= 1)) {
        $map['COMMENT_COUNT'] = '1';
    }
    return do_template('POLL_BOX', $map);
}

/**
 * Vote in a poll.
 *
 * @param  AUTO_LINK                    The poll ID
 * @param  ?integer                     Vote to cast (NULL: forfeit vote)
 * @param  ?array                       Poll row (NULL: lookup from DB)
 * @param  ?MEMBER                      Who to vote (NULL: current user)
 * @param  ?IP                          The IP to vote (NULL: no IP check)
 * @return array                        Amended poll row
 */
function vote_in_poll($poll_id, $cast, $myrow = null, $member_id = null, $ip = null)
{
    if (is_null($member_id)) {
        $member_id = get_member();
    }
    if (is_null($ip)) {
        $ip = get_ip_address();
    }

    if (is_null($myrow)) {
        $rows = $GLOBALS['SITE_DB']->query_select('poll', array('*'), array('id' => $poll_id), '', 1);
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $myrow = $rows[0];
    }

    if (!is_null($cast)) {
        if (may_vote_in_poll($poll_id, $member_id, $ip)) {
            if (addon_installed('points')) {
                require_code('points');
                $_before = point_info($member_id);
                $before = array_key_exists('points_gained_voting', $_before) ? $_before['points_gained_voting'] : 0;
                $GLOBALS['FORUM_DRIVER']->set_custom_field($member_id, 'points_gained_voting', $before + 1);
            }
            $GLOBALS['SITE_DB']->query_update(
                'poll',
                array(('votes' . strval($cast)) => ($myrow['votes' . strval($cast)] + 1)),
                array('id' => $poll_id),
                '',
                1
            );

            $GLOBALS['SITE_DB']->query_insert('poll_votes', array(
                'v_poll_id' => $poll_id,
                'v_voter_id' => $member_id,
                'v_voter_ip' => $ip,
                'v_vote_for' => $cast,
            ));

            $myrow['votes' . strval($cast)]++;
        }
    } else {
        $GLOBALS['SITE_DB']->query_insert('poll_votes', array(
            'v_poll_id' => $poll_id,
            'v_voter_id' => is_guest() ? null : $member_id,
            'v_voter_ip' => $ip,
            'v_vote_for' => null,
        ));
    }

    return $myrow;
}

/**
 * Find whether the current member may vote.
 *
 * @param  AUTO_LINK                    The poll ID
 * @param  MEMBER                       Who to check for
 * @param  ?IP                          The IP to check for (NULL: no IP check)
 * @return boolean                      Whether the current member may vote
 */
function may_vote_in_poll($poll_id, $member_id, $ip)
{
    if (($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (get_param_integer('keep_rating_test', 0) == 1)) {
        return true;
    }

    if (!has_privilege($member_id, 'vote_in_polls', 'cms_polls')) {
        return false;
    }

    if ((get_option('vote_member_ip_restrict') == '0') || (is_null($ip))) {
        if (is_guest($member_id)) {
            if (is_null($ip)) {
                return true;
            }
            return is_null($GLOBALS['SITE_DB']->query_select_value_if_there('poll_votes', 'id', array('v_poll_id' => $poll_id, 'v_voter_ip' => $ip)));
        } else {
            return is_null($GLOBALS['SITE_DB']->query_select_value_if_there('poll_votes', 'id', array('v_poll_id' => $poll_id, 'v_voter_id' => $member_id)));
        }
    }

    return is_null($GLOBALS['SITE_DB']->query_value_if_there('SELECT id FROM ' . get_table_prefix() . 'poll_votes WHERE v_poll_id=' . strval($poll_id) . ' AND (v_voter_id=' . strval($member_id) . ' OR ' . db_string_equal_to('v_voter_ip', $ip) . ')'));
}

/**
 * Get a list of polls.
 *
 * @param  ?AUTO_LINK                   The ID of the poll to select by default (NULL: first)
 * @param  ?MEMBER                      Only show polls owned by this member (NULL: no such restriction)
 * @return tempcode                     The list
 */
function create_selection_list_polls($it = null, $only_owned = null)
{
    $where = is_null($only_owned) ? null : array('submitter' => $only_owned);
    $rows = $GLOBALS['SITE_DB']->query_select('poll', array('question', 'is_current', 'votes1', 'votes2', 'votes3', 'votes4', 'votes5', 'votes6', 'votes7', 'votes8', 'votes9', 'votes10', 'id'), $where, 'ORDER BY is_current DESC,date_and_time,question', 400);
    if (count($rows) == 400) { // Ok, just new ones
        if (is_null($where)) {
            $where = array();
        }
        $rows = $GLOBALS['SITE_DB']->query_select('poll', array('question', 'is_current', 'votes1', 'votes2', 'votes3', 'votes4', 'votes5', 'votes6', 'votes7', 'votes8', 'votes9', 'votes10', 'id'), $where + array('date_and_time' => null), 'ORDER BY add_time DESC', 400);
    }
    $out = new ocp_tempcode();
    foreach ($rows as $myrow) {
        $selected = !is_null($it);

        if ($myrow['is_current'] == 1) {
            $status = do_lang_tempcode('CURRENT');
            if (is_null($it)) {
                $selected = true;
            }
        } else {
            // If people have voted the IP field will have something in it. So we can tell if its new or not from this
            if ($myrow['votes1'] + $myrow['votes2'] + $myrow['votes3'] + $myrow['votes4'] + $myrow['votes5'] + $myrow['votes6'] + $myrow['votes7'] + $myrow['votes8'] + $myrow['votes9'] + $myrow['votes10'] != 0) {
                $status = do_lang_tempcode('USED_PREVIOUSLY');
            } else {
                $status = do_lang_tempcode('NOT_USED_PREVIOUSLY');
            }
        }
        $text = do_template('POLL_LIST_ENTRY', array('_GUID' => 'dadf669bca2add9b79329b21e45d1010', 'QUESTION' => get_translated_text($myrow['question']), 'STATUS' => $status));
        $out->attach(form_input_list_entry(strval($myrow['id']), $selected, $text));
    }

    return $out;
}
