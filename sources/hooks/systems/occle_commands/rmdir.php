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
 * @package		occle
 */

class Hook_occle_command_rmdir
{
    /**
	 * Run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  object A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
    public function run($options,$parameters,&$occle_fs)
    {
        if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) {
            return array('',do_command_help('rmdir',array('h','f'),array(true)),'','');
        } else {
            if (!array_key_exists(0,$parameters)) {
                return array('','','',do_lang('MISSING_PARAM','1','rmdir'));
            } else {
                $parameters[0] = $occle_fs->_pwd_to_array($parameters[0]);
            }

            if (!$occle_fs->_is_dir($parameters[0])) {
                return array('','','',do_lang('NOT_A_DIR','1'));
            }

            if (!array_key_exists('f',$options)) {
                $listing = $occle_fs->listing($parameters[0]);
                if ((count($listing[0]) != 0) || (count($listing[1]) != 0)) {
                    return array('','','',do_lang('NOT_EMPTY_FORCE','1'));
                }
            }

            $success = $occle_fs->remove_directory($parameters[0]);
            if ($success) {
                return array('','',do_lang('SUCCESS'),'');
            } else {
                return array('','','',do_lang('INCOMPLETE_ERROR'));
            }
        }
    }
}
