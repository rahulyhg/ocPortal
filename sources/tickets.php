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
 * @package		tickets
 */

/**
 * Build a list of ticket types.
 *
 * @param  ?AUTO_LINK	The current selected ticket type (NULL: none)
 * @param  ?array			List of ticket types to show regardless of access permissions (NULL: none)
 * @return array			A map between ticket types, and template-ready details about them
 */
function build_types_list($selected_ticket_type_id,$ticket_types_to_let_through = null)
{
    if (is_null($ticket_types_to_let_through)) {
        $ticket_types_to_let_through = array();
    }

    $_types = $GLOBALS['SITE_DB']->query_select('ticket_types',array('id','ticket_type_name','cache_lead_time'),null,'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('ticket_type_name'));
    $types = array();
    foreach ($_types as $type) {
        if ((!has_category_access(get_member(),'tickets',strval($type['id']))) && (!in_array($type['id'],$ticket_types_to_let_through))) {
            continue;
        }

        if (is_null($type['cache_lead_time'])) {
            $lead_time = do_lang('UNKNOWN');
        } else {
            $lead_time = display_time_period($type['cache_lead_time']);
        }
        $types[$type['id']] = array('TICKET_TYPE_ID' => strval($type['id']),'SELECTED' => ($type['id'] === $selected_ticket_type_id),'NAME' => get_translated_text($type['ticket_type_name']),'LEAD_TIME' => $lead_time);
    }
    return $types;
}

/**
 * Checks the ticket ID is valid, and there is access for the current member to view it. Bombs out if there's a problem.
 *
 * @param  string			The ticket ID to check
 * @return MEMBER			The ticket owner
 */
function check_ticket_access($id)
{
    // Never for a guest
    if (is_guest()) {
        access_denied('NOT_AS_GUEST');
    }

    // Check we are allowed using normal checks
    $_temp = explode('_',$id);
    $ticket_owner = intval($_temp[0]);
    if (array_key_exists(2,$_temp)) {
        log_hack_attack_and_exit('TICKET_SYSTEM_WEIRD');
    }
    if (has_privilege(get_member(),'view_others_tickets')) {
        return $ticket_owner;
    }
    if ($ticket_owner == get_member()) {
        return $ticket_owner;
    }

    // Check we're allowed using extra access
    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_extra_access','ticket_id',array('ticket_id' => $id,'member_id' => get_member()));
    if (!is_null($test)) {
        return $ticket_owner;
    }

    // No access :(
    if (is_guest(intval($_temp[0]))) {
        access_denied(do_lang('TICKET_OTHERS_HACK'));
    }
    log_hack_attack_and_exit('TICKET_OTHERS_HACK');

    return $ticket_owner; // Will never get here
}

/**
 * Get the forum ID for a given ticket type and member, taking the ticket_member_forums and ticket_type_forums options
 * into account.
 *
 * @param  ?AUTO_LINK		The member ID (NULL: no member)
 * @param  ?integer			The ticket type (NULL: all ticket types)
 * @param  boolean			Create the forum if it's missing
 * @param  boolean			Whether to skip showing errors, returning NULL instead
 * @return ?AUTO_LINK		Forum ID (NULL: not found)
 */
function get_ticket_forum_id($member = null,$ticket_type_id = null,$create = false,$silent_error_handling = false)
{
    static $fid_cache = array();
    if (isset($fid_cache[$member][$ticket_type_id])) {
        return $fid_cache[$member][$ticket_type_id];
    }

    $root_forum = get_option('ticket_forum_name');

    // Check the root ticket forum is valid
    $fid = $GLOBALS['FORUM_DRIVER']->forum_id_from_name($root_forum);
    if (is_null($fid)) {
        if ($silent_error_handling) {
            return NULL;
        }
        warn_exit(do_lang_tempcode('NO_FORUM'));
    }

    // Only the root ticket forum is supported for non-OCF installations
    if (get_forum_type() != 'ocf') {
        return $fid;
    }

    require_code('ocf_forums_action');
    require_code('ocf_forums_action2');

    $category_id = $GLOBALS['FORUM_DB']->query_select_value('f_forums','f_forum_grouping_id',array('id' => $fid));

    if ((!is_null($member)) && (get_option('ticket_member_forums') == '1')) {
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member);
        $rows = $GLOBALS['FORUM_DB']->query_select('f_forums',array('id'),array('f_parent_forum' => $fid,'f_name' => $username),'',1);
        if (count($rows) == 0) {
            $fid = ocf_make_forum($username,do_lang('SUPPORT_TICKETS_FOR_MEMBER',$username),$category_id,null,$fid);
        } else {
            $fid = $rows[0]['id'];
        }
    }

    if ((!is_null($ticket_type_id)) && (get_option('ticket_type_forums') == '1')) {
        $_ticket_type_name = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types','ticket_type_name',array('id' => $ticket_type_id));
        if (!is_null($_ticket_type_name)) {
            $ticket_type_name = get_translated_text($_ticket_type_name);
            $rows = $GLOBALS['FORUM_DB']->query_select('f_forums',array('id'),array('f_parent_forum' => $fid,'f_name' => $ticket_type_name),'',1);
            if (count($rows) == 0) {
                $fid = ocf_make_forum($ticket_type_name,do_lang('SUPPORT_TICKETS_FOR_TYPE',$ticket_type_name),$category_id,null,$fid);
            } else {
                $fid = $rows[0]['id'];
            }
        }
    }

    $fid_cache[$member][$ticket_type_id] = $fid;

    return $fid;
}

/**
 * Returns whether the given forum ID is for a ticket forum (subforum of the root ticket forum).
 *
 * @param  ?AUTO_LINK	The forum ID (NULL: private topics forum)
 * @return boolean		Whether the given forum is a ticket forum
 */
function is_ticket_forum($forum_id)
{
    if (is_null($forum_id)) {
        return false;
    }

    $root_ticket_forum_id = get_ticket_forum_id(null,null,false,true);
    if (($root_ticket_forum_id == db_get_first_id()) && ($forum_id != db_get_first_id())) {
        return false;
    } // If ticket forum (oddly) set as root, don't cascade it through all!
    if ($forum_id === $root_ticket_forum_id) {
        return true;
    }

    $query = 'SELECT COUNT(*) AS cnt FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($forum_id) . ' AND f_parent_forum IN (SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($root_ticket_forum_id) . ' OR f_parent_forum IN (SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($root_ticket_forum_id) . '))';

    $rows = $GLOBALS['FORUM_DB']->query($query);
    return ($rows[0]['cnt'] != 0);
}
