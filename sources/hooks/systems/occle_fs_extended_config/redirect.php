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
 * @package    redirects_editor
 */

/**
 * Hook class.
 */
class Hook_occle_fs_extended_config__redirect
{
    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @return ?TIME                    The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_edit_date()
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('the_type', 'SET_REDIRECTS');
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs file reading function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $file_name The file name
     * @param  object                    &$occle_fs A reference to the OcCLE filesystem object
     * @return ~string                  The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$occle_fs)
    {
        $rows = $GLOBALS['SITE_DB']->query_select('redirects', array('*'));
        return serialize($rows);
    }

    /**
     * Standard occle_fs file writing function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $file_name The file name
     * @param  string                   $contents The new file contents
     * @param  object                    &$occle_fs A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$occle_fs)
    {
        $GLOBALS['SITE_DB']->query_delete('redirects');
        $rows = @unserialize($contents);
        if ($rows === false) {
            return false;
        }
        foreach ($rows as $row) {
            $GLOBALS['SITE_DB']->query_insert('redirects', $row);
        }
        return true;
    }
}
