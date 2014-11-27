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
class Hook_occle_command_occlechat
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
            return array('', do_command_help('occlechat', array('h'), array(true, true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'occlechat'));
            }
            if (!array_key_exists(1, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '2', 'occlechat'));
            }

            $GLOBALS['SITE_DB']->query_insert('occlechat', array('c_message' => $parameters[1], 'c_url' => $parameters[0], 'c_incoming' => 0, 'c_timestamp' => time()));
            $url = $parameters[0] . '/data/occle.php?action=message&base_url=' . urlencode(get_base_url()) . '&message=' . urlencode($parameters[1]);
            $return = http_download_file($url, null, false);
            if (is_null($return)) {
                return array('', '', '', do_lang('HTTP_DOWNLOAD_NO_SERVER', $parameters[0]));
            } elseif ($return == '1') {
                return array('', '', do_lang('SUCCESS'), '');
            } else {
                return array('', '', '', do_lang('INCOMPLETE_ERROR'));
            }
        }
    }
}
