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
 * @package    awards
 */

/**
 * Make an award type.
 *
 * @param  SHORT_TEXT                   $title The title
 * @param  LONG_TEXT                    $description The description
 * @param  integer                      $points How many points are given to the awardee
 * @param  ID_TEXT                      $content_type The content type the award type is for
 * @param  BINARY                       $hide_awardee Whether to not show the awardee when displaying this award
 * @param  integer                      $update_time_hours The approximate time in hours between awards (e.g. 168 for a week)
 * @return AUTO_LINK                    The ID
 */
function add_award_type($title, $description, $points, $content_type, $hide_awardee, $update_time_hours)
{
    require_code('global4');
    prevent_double_submit('ADD_AWARD_TYPE', null, $title);

    $map = array(
        'a_points' => $points,
        'a_content_type' => filter_naughty_harsh($content_type),
        'a_hide_awardee' => $hide_awardee,
        'a_update_time_hours' => $update_time_hours,
    );
    $map += insert_lang('a_title', $title, 2);
    $map += insert_lang_comcode('a_description', $description, 2);
    $id = $GLOBALS['SITE_DB']->query_insert('award_types', $map, true);

    log_it('ADD_AWARD_TYPE', strval($id), $title);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('award_type', strval($id), null, null, true);
    }

    return $id;
}

/**
 * Edit an award type
 *
 * @param  AUTO_LINK                    $id The ID
 * @param  SHORT_TEXT                   $title The title
 * @param  LONG_TEXT                    $description The description
 * @param  integer                      $points How many points are given to the awardee
 * @param  ID_TEXT                      $content_type The content type the award type is for
 * @param  BINARY                       $hide_awardee Whether to not show the awardee when displaying this award
 * @param  integer                      $update_time_hours The approximate time in hours between awards (e.g. 168 for a week)
 */
function edit_award_type($id, $title, $description, $points, $content_type, $hide_awardee, $update_time_hours)
{
    $_title = $GLOBALS['SITE_DB']->query_select_value_if_there('award_types', 'a_title', array('id' => $id));
    if (is_null($_title)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $_description = $GLOBALS['SITE_DB']->query_select_value('award_types', 'a_description', array('id' => $id));
    $map = array(
        'a_points' => $points,
        'a_content_type' => filter_naughty_harsh($content_type),
        'a_hide_awardee' => $hide_awardee,
        'a_update_time_hours' => $update_time_hours,
    );
    $map += lang_remap('a_title', $_title, $title);
    $map += lang_remap_comcode('a_description', $_description, $description);
    $GLOBALS['SITE_DB']->query_update('award_types', $map, array('id' => $id));

    log_it('EDIT_AWARD_TYPE', strval($id), $title);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('award_type', strval($id));
    }
}

/**
 * Delete an award type.
 *
 * @param  AUTO_LINK                    $id The ID
 */
function delete_award_type($id)
{
    $_title = $GLOBALS['SITE_DB']->query_select_value_if_there('award_types', 'a_title', array('id' => $id));
    if (is_null($_title)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $_description = $GLOBALS['SITE_DB']->query_select_value('award_types', 'a_description', array('id' => $id));
    log_it('DELETE_AWARD_TYPE', strval($id), get_translated_text($_title));
    $GLOBALS['SITE_DB']->query_delete('award_types', array('id' => $id), '', 1);
    $GLOBALS['SITE_DB']->query_delete('award_archive', array('a_type_id' => $id), '', 1);
    delete_lang($_title);
    delete_lang($_description);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resourcefs_moniker('award_type', strval($id));
    }
}
