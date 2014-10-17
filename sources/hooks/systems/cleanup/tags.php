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
 * @package    core_cleanup_tools
 */
class Hook_tags
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array                   Map of cleanup hook info (NULL: hook is disabled).
     */
    public function info()
    {
        $info = array();
        $info['title'] = do_lang_tempcode('ORPHANED_TAGS');
        $info['description'] = do_lang_tempcode('DESCRIPTION_ORPHANED_TAGS');
        $info['type'] = 'cache';

        return $info;
    }

    /**
     * Run the cleanup hook action.
     *
     * @return tempcode                 Results
     */
    public function run()
    {
        $hooks = find_all_hooks('systems', 'content_meta_aware');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/content_meta_aware/' . $hook);
            $ob = object_factory('Hook_content_meta_aware_' . $hook);
            $info = $ob->info();
            $seo_type_code = $info['seo_type_code'];
            if (!is_null($seo_type_code)) {
                $table = $info['table'];

                $id_field = $info['id_field'];

                if (($table == 'comcode_pages') || (is_array($id_field))) {
                    continue;
                } // Can't handle these cases

                $sql = 'SELECT m.* FROM ' . get_table_prefix() . 'seo_meta m';
                $sql .= ' LEFT JOIN ' . get_table_prefix() . $table . ' r ON r.' . $id_field . '=m.meta_for_id AND ' . db_string_equal_to('m.meta_for_type', $seo_type_code);
                $sql .= ' WHERE r.' . $id_field . ' IS NULL AND ' . db_string_equal_to('m.meta_for_type', $seo_type_code);
                $orphaned = $GLOBALS[(substr($table, 0, 2) == 'f_') ? 'FORUM_DB' : 'SITE_DB']->query($sql);
                if (count($orphaned) != 0) {
                    foreach ($orphaned as $o) {
                        delete_lang($o['meta_keywords']);
                        delete_lang($o['meta_description']);
                        $GLOBALS['SITE_DB']->query_delete('seo_meta', array('id' => $o['id']), '', 1);
                    }
                }
            }
        }

        decache('side_tag_cloud');

        return new ocp_tempcode();
    }
}