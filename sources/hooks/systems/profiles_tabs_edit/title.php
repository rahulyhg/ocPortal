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
 * @package    ocf_member_titles
 */

/**
 * Hook class.
 */
class Hook_profiles_tabs_edit_title
{
    /**
     * Find whether this hook is active.
     *
     * @param  MEMBER                   $member_id_of The ID of the member who is being viewed
     * @param  MEMBER                   $member_id_viewing The ID of the member who is doing the viewing
     * @return boolean                  Whether this hook is active
     */
    public function is_active($member_id_of, $member_id_viewing)
    {
        return has_privilege($member_id_viewing, 'may_choose_custom_title') && (($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing, 'assume_any_member')) || (has_privilege($member_id_viewing, 'member_maintenance')));
    }

    /**
     * Render function for profile tabs edit hooks.
     *
     * @param  MEMBER                   $member_id_of The ID of the member who is being viewed
     * @param  MEMBER                   $member_id_viewing The ID of the member who is doing the viewing
     * @param  boolean                  $leave_to_ajax_if_possible Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
     * @return ?array                   A tuple: The tab title, the tab body text (may be blank), the tab fields, extra JavaScript (may be blank) the suggested tab order, hidden fields (optional) (null: if $leave_to_ajax_if_possible was set), the icon
     */
    public function render_tab($member_id_of, $member_id_viewing, $leave_to_ajax_if_possible = false)
    {
        $title = do_lang_tempcode('MEMBER_TITLE');

        $order = 50;

        // Actualiser
        $_title = post_param('member_title', null);
        if ($_title !== null) {
            require_code('ocf_members_action');
            require_code('ocf_members_action2');
            ocf_member_choose_title($_title, $member_id_of);

            attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
        }

        if ($leave_to_ajax_if_possible) {
            return null;
        }

        // UI fields
        $fields = new Tempcode();
        $_title = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_title');
        require_code('form_templates');
        $fields->attach(form_input_line(do_lang_tempcode('MEMBER_TITLE'), '', 'member_title', $_title, false, null, intval(get_option('max_member_title_length'))));

        $text = do_lang_tempcode('DESCRIPTION_MEMBER_TITLE', escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id_of, true)));

        $javascript = '';

        return array($title, $fields, $text, $javascript, $order, null, 'tabs/member_account/edit/title');
    }
}
