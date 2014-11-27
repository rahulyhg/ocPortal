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
 * @package    occle
 */

/**
 * Hook class.
 */
class Hook_occle_fs_root
{
    /**
     * Standard occle_fs listing function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return ~array                   The final directory listing (false: failure)
     */
    public function listing($meta_dir, $meta_root_node, &$occle_fs)
    {
        $override_path = $this->_customise_directory($meta_dir);
        $nonoverride_path = $this->_customise_directory($meta_dir, false);

        $listing = array();
        $ok = false;
        foreach (array($override_path, $nonoverride_path) as $path) {
            if (is_dir($path)) {
                $dh = opendir($path);
                while (($file = readdir($dh)) !== false) {
                    if (($file != '.') && ($file != '..') && ($file != '.git') && ((strpos($file, '_custom') === false) || (!file_exists($path . str_replace('_custom', '', $file))))) {
                        $listing[] = array(
                            $file,
                            is_dir($path . $file) ? OCCLEFS_DIR : OCCLEFS_FILE,
                            is_dir($path . $file) ? null : filesize($path . $file),
                            filemtime($path . $file),
                        );
                    }
                }
                $ok = true;
            }
        }
        if ($ok) {
            return $listing;
        }

        return false; // Directory doesn't exist
    }

    /**
     * Standard occle_fs directory creation function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $new_dir_name The new directory name
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function make_directory($meta_dir, $meta_root_node, $new_dir_name, &$occle_fs)
    {
        $new_dir_name = filter_naughty($new_dir_name);
        $path = $this->_customise_directory($meta_dir);

        if ((is_dir($path)) && (!file_exists($path . $new_dir_name)) && (is_writable_wrap($path))) {
            $ret = @mkdir($path . $new_dir_name, 0777) or warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY', $path . $new_dir_name));
            fix_permissions($path . $new_dir_name, 0777);
            sync_file($path . $new_dir_name);
            return $ret;
        } else {
            return false; // Directory exists
        }
    }

    /**
     * Standard occle_fs directory removal function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $dir_name The directory name
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function remove_directory($meta_dir, $meta_root_node, $dir_name, &$occle_fs)
    {
        $dir_name = filter_naughty($dir_name);
        $path = $this->_customise_directory($meta_dir);

        if ((is_dir($path)) && (file_exists($path . $dir_name)) && (is_writable_wrap($path . $dir_name))) {
            require_code('files');
            deldir_contents($path . $dir_name);
            $ret = @rmdir($path . $dir_name) or warn_exit(do_lang_tempcode('WRITE_ERROR', escape_html($path . $dir_name)));
            sync_file($path . $dir_name);
            return true;
        } else {
            return false; // Directory doesn't exist
        }
    }

    /**
     * Standard occle_fs file removal function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $file_name The file name
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function remove_file($meta_dir, $meta_root_node, $file_name, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = $this->_customise_directory($meta_dir);

        if ((is_dir($path)) && (file_exists($path . $file_name)) && (is_writable_wrap($path . $file_name))) {
            $ret = @unlink($path . $file_name) or intelligent_write_error($path . $file_name);
            sync_file($path . $file_name);
            return $ret;
        } else {
            return false; // File doesn't exist
        }
    }

    /**
     * Standard occle_fs file reading function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $file_name The file name
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return ~string                  The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = $this->_customise_directory($meta_dir);
        if (!file_exists($path . $file_name)) {
            $path = $this->_customise_directory($meta_dir, false);
        }

        if ((is_dir($path)) && (file_exists($path . $file_name)) && (is_readable($path . $file_name))) {
            return file_get_contents($path . $file_name);
        } else {
            return false; // File doesn't exist
        }
    }

    /**
     * Standard occle_fs file writing function for OcCLE FS hooks.
     *
     * @param  array                    $meta_dir The current meta-directory path
     * @param  string                   $meta_root_node The root node of the current meta-directory
     * @param  string                   $file_name The file name
     * @param  string                   $contents The new file contents
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = $this->_customise_directory($meta_dir);

        if ((is_dir($path)) && (((file_exists($path . $file_name)) && (is_writable_wrap($path . $file_name))) || ((!file_exists($path . $file_name)) && (is_writable_wrap($path))))) {
            $fh = @fopen($path . $file_name, GOOGLE_APPENGINE ? 'wb' : 'wt') or intelligent_write_error($path . $file_name);
            $output = fwrite($fh, $contents);
            fclose($fh);
            if ($output < strlen($contents)) {
                warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
            }
            fix_permissions($path . $file_name);
            sync_file($path . $file_name);
            return $output;
        } else {
            return false; // File doesn't exist
        }
    }

    /**
     * Customise a directory path, adding _custom to appropriate entries.
     *
     * @param  array                    $directory Path to customise
     * @param  boolean                  $change_to_override Whether to rewrite to be the alternative override directory
     * @return string                   Customised path
     */
    protected function _customise_directory($directory, $change_to_override = true)
    {
        $dir_replacements = array('sources', 'comcode', 'html', 'minimodules', 'modules', 'data', 'lang', 'text', 'images', 'templates');

        $path = get_custom_file_base() . '/';
        $previous_dir_section = '';
        foreach ($directory as $dir_section) {
            if ($change_to_override) {
                if (in_array($dir_section, $dir_replacements)) {
                    $dir_section .= '_custom';
                }
            }

            $path .= filter_naughty($dir_section) . '/';
            $previous_dir_section = $dir_section;
        }

        if (!file_exists($path)) {
            $ret = @mkdir($path, 0777, true) or warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY', $path));
            fix_permissions($path, 0777);
            sync_file($path);
        }

        return $path;
    }
}
