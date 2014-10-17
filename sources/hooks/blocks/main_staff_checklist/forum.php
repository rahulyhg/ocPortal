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
 * @package    core_adminzone_dashboard
 */
class Hook_checklist_forum
{
    /**
     * Find items to include on the staff checklist.
     *
     * @return array                    An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
     */
    public function run()
    {
        // Forum moderation
        if (!has_no_forum()) {
            $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_NA');
            if (get_forum_type() == 'ocf') {
                $url = build_url(array('page' => 'vforums', 'type' => 'unread'), get_module_zone('vforums'));
            } else {
                $url = make_string_tempcode(get_forum_base_url());
            }
            $tpl = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM', array('_GUID' => 'a2cdfc2ea5db2d8c13a4d9eafa9b644b', 'URL' => '', 'STATUS' => $status, 'TASK' => urlise_lang(do_lang('NAG_FORUMS'), $url), 'INFO' => ''));
            return array(array($tpl, null, null, null));
        }
        return array();
    }
}
