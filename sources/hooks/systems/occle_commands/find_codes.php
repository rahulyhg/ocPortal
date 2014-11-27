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
class Hook_occle_command_find_codes
{
    /**
     * Run function for OcCLE hooks.
     *
     * @param  array                    $options The options with which the command was called
     * @param  array                    $parameters The parameters with which the command was called
     * @param  object                    &$occle_fs A reference to the OcCLE filesystem object
     * @return array                    Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$occle_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('find_codes', array('h'), array(true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'find_codes'));
            }

            $path = get_custom_file_base() . '/sources/';
            $files = array();

            if (is_dir($path)) {
                $dh = opendir($path);
                while (($file = readdir($dh)) !== false) {
                    if (($file != '.') && ($file != '..')) {
                        if (!is_dir($path . $file)) {
                            $contents = file_get_contents($path . $file);
                            if (strpos($contents, $parameters[0]) !== false) {
                                $files[] = $path . $file;
                            }
                        }
                        unset($contents); // Got to be careful with that memory :-(
                    }
                }

                return array('', do_template('OCCLE_FIND_CODES', array('_GUID' => '3374d1a80727aecc271722f2184743d0', 'FILES' => $files)), '', '');
            } else {
                return array('', '', '', do_lang('INCOMPLETE_ERROR')); // Directory doesn't exist
            }
        }
    }
}
