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
 * @package		chat
 */

/**
 * Get the list of all available chat sound effects.
 *
 * @param  boolean		Map to NULL if it is not overridable.
 * @return map				All available sound effects (mapping between base code, and actual code).
*/
function get_effect_set($only_overridable = false)
{
    $effects = array(
        'message_received' => 'message_received',
        'message_background' => 'message_background',
        'message_initial' => $only_overridable?null:'message_initial',
        'message_sent' => $only_overridable?null:'message_sent',
        'contact_on' => 'contact_on',
        'contact_off' => 'contact_off',
        'invited' => 'invited',
        'you_connect' => $only_overridable?null:'you_connect',
    );

    return $effects;
}

/**
 * Get a list of template mappings for the current member, between sound effect IDs and the URLs to the mp3 fiels.
 *
 * @param  boolean		Whether to use full URLs in the mappings.
 * @param  ?MEMBER		Get settings overridden for this specific member (NULL: global settings).
 * @param  boolean		Get global settings and settings overridden for all members (if this is true we'd expect $for_member to be NULL).
 * @return array			The template mappings.
*/
function get_effect_settings($full_urls = false,$for_member = null,$all_members = false)
{
    $effects = get_effect_set(!is_null($for_member));

    global $EFFECT_SETTINGS_ROWS;
    if (is_null($EFFECT_SETTINGS_ROWS)) {
        $EFFECT_SETTINGS_ROWS = collapse_2d_complexity('s_effect_id','s_url',$GLOBALS['SITE_DB']->query_select('chat_sound_effects',array('s_url','s_effect_id'),array('s_member' => get_member())));
    }
    $effect_settings = array();
    if ($all_members) {
        foreach (array_keys($EFFECT_SETTINGS_ROWS) as $effect_id) {
            $matches = array();
            if ((!array_key_exists($effect_id,$effects)) && (preg_match('#^(.*)\_(\d+)$#',$effect_id,$matches) != 0) && (array_key_exists($matches[1],$effects))) {
                $effects[$effect_id] = $matches[1];
            }
        }
    }
    foreach ($effects as $effect => $base_effect_code) {
        if (is_null($base_effect_code)) {
            continue;
        }

        if (is_null($for_member)) { // Global settings
            if (array_key_exists($effect,$EFFECT_SETTINGS_ROWS)) {
                $member_setting = $EFFECT_SETTINGS_ROWS[$effect];
            } else {
                $member_setting = 'data_custom/sounds/' . $effect . '.mp3';
                if (!file_exists(get_custom_file_base() . '/' . $member_setting)) {
                    $member_setting = 'data/sounds/' . $effect . '.mp3';
                }
                if (!file_exists(get_file_base() . '/' . $member_setting)) {
                    $member_setting = '';
                }
            }
        } else { // Overridden settings
            if (array_key_exists($effect . '_' . strval($for_member),$EFFECT_SETTINGS_ROWS)) {
                $member_setting = $EFFECT_SETTINGS_ROWS[$effect . '_' . strval($for_member)];
            } else {
                $member_setting = '-1';
            }
        }
        $effect_settings[$effect] = array(
            'KEY' => $effect,
            'VALUE' => (($full_urls && ($member_setting != ''))?(((substr($member_setting,0,12) == 'data_custom/')?get_custom_base_url():get_base_url()) . '/'):'') . $member_setting,
            'EFFECT_TITLE' => do_lang('CHAT_EFFECT_' . $base_effect_code),
        );
    }
    return $effect_settings;
}
