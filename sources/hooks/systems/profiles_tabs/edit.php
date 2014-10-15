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
 * @package		core_ocf
 */

class Hook_Profiles_Tabs_edit
{
    /**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
    public function is_active($member_id_of,$member_id_viewing)
    {
        if (is_guest($member_id_viewing)) {
            return false;
        }

        if (!(($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing,'assume_any_member')) || (has_privilege($member_id_viewing,'member_maintenance')))) {
            return false;
        }

        $hooks = find_all_hooks('systems','profiles_tabs_edit');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/profiles_tabs_edit/' . $hook);
            $ob = object_factory('Hook_Profiles_Tabs_Edit_' . $hook);
            if ($ob->is_active($member_id_of,$member_id_viewing)) {
                return true;
            }
        }

        return false;
    }

    /**
	 * Render function for profile tab hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @param  boolean		Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
	 * @return array			A tuple: The tab title, the tab contents, the suggested tab order, the icon
	 */
    public function render_tab($member_id_of,$member_id_viewing,$leave_to_ajax_if_possible = false)
    {
        $title = do_lang_tempcode('EDIT_EM');

        require_lang('ocf');
        require_css('ocf');

        $GLOBALS['NO_QUERY_LIMIT'] = true;

        $order = 200;

        $only_tab = get_param('only_subtab',null);

        if (($leave_to_ajax_if_possible) && (strtoupper(ocp_srv('REQUEST_METHOD')) != 'POST')) {
            return array($title,null,$order,'tabs/settings');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(60);
        } // Raise time limit, as can be slow

        $tabs = array();

        $hooks = find_all_hooks('systems','profiles_tabs_edit');
        if (isset($hooks['settings'])) { // Editing must go first, so changes reflect in the renders of the tabs
            $hooks = array('settings' => $hooks['settings'])+$hooks;
        }
        foreach (array_keys($hooks) as $hook) {
            if (($only_tab === NULL) || (preg_match('#(^|,)' . preg_quote($hook,'#') . '(,|$)#',$only_tab) != 0)) {
                require_code('hooks/systems/profiles_tabs_edit/' . $hook);
                $ob = object_factory('Hook_Profiles_Tabs_Edit_' . $hook);
                if ($ob->is_active($member_id_of,$member_id_viewing)) {
                    $tab = $ob->render_tab($member_id_of,$member_id_viewing,$only_tab !== $hook && $leave_to_ajax_if_possible);

                    if ($only_tab === $hook) {
                        $title = $tab[0];
                    }

                    $tabs[] = $tab;
                }
            }
        }

        if ($leave_to_ajax_if_possible) {
            return array($title,null,$order,'tabs/settings');
        }

        sort_maps_by($tabs,4);
        $tabs = array_values($tabs); // Reindex, needed for lastness check

        $javascript = '';

        $hidden = new ocp_tempcode();

        // Session ID check, if saving
        if ((count($_POST) != 0) && (count($tabs) != 0)) {
            global $SESSION_CONFIRMED_CACHE;
            if (($SESSION_CONFIRMED_CACHE == 0) && ((post_param('edit_password','') != '') || ($member_id_viewing != $member_id_of))) {
                access_denied('SESSION','',true);
            }
        }

        $_tabs = array();
        $tab_first = true;
        foreach ($tabs as $i => $tab) {
            if (is_null($tab)) {
                continue;
            }

            $javascript .= $tab[3];

            $tab_last = true;
            foreach ($tabs as $j => $tabj) {
                if ($j>$i) {
                    if (!is_null($tabj)) {
                        $tab_last = false;
                        break;
                    }
                }
            }

            $single_field = (array_key_exists(7,$tab)?$tab[7]:false);

            if (isset($tab[5])) {
                $hidden->attach($tab[5]);
            }

            $_tabs[] = array(
                'TAB_TITLE' => $tab[0],
                'TAB_FIELDS' => $tab[1],
                'TAB_ICON' => $tab[6],
                'TAB_TEXT' => $tab[2],
                'TAB_FIRST' => $tab_first,
                'TAB_LAST' => $tab_last,
                'TAB_SINGLE_FIELD' => $single_field,
            );

            $tab_first = false;
        }

        $url = build_url(array('page' => '_SELF'),'_SELF',null,true,false,false/*,'tab__edit'  confusing, esp if was not on settings edit tab initially*/);

        $content = do_template('OCF_MEMBER_PROFILE_EDIT',array(
            '_GUID' => '7a3e2cc210583fe4f3097af48b052351',
            'JAVASCRIPT' => $javascript,
            'HIDDEN' => $hidden,
            'URL' => $url,
            'SUBMIT_ICON' => 'buttons__save',
            'SUBMIT_NAME' => do_lang_tempcode('SAVE'),
            'AUTOCOMPLETE' => false,
            'SKIP_VALIDATION' => true,
            'TABS' => $_tabs,
        ));

        return array($title,$content,$order,'tabs/settings');
    }
}
