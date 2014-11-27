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
 * @package    pointstore
 */

/**
 * Hook class.
 */
class Hook_page_groupings_pointstore
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  $member_id Member ID to run as (null: current member)
     * @param  boolean                  $extensive_docs Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        if (!addon_installed('pointstore')) {
            return array();
        }

        return array(
            array('audit', 'menu/adminzone/audit/pointstore_log', array('admin_pointstore', array('type' => 'browse'), get_module_zone('admin_pointstore')), do_lang_tempcode('pointstore:POINTSTORE_MANAGE_SALES'), 'pointstore:DOC_POINTSTORE'),
            array('setup', 'menu/social/pointstore', array('admin_pointstore', array('type' => 'p'), get_module_zone('admin_pointstore')), do_lang_tempcode('pointstore:POINTSTORE_MANAGE_INVENTORY'), 'pointstore:DOC_POINTSTORE'),
            array('social', 'menu/social/pointstore', array('pointstore', array(), get_module_zone('pointstore')), do_lang_tempcode('pointstore:POINTSTORE')),
        );
    }
}
