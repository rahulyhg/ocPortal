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
class Hook_occle_command_help
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
        if (array_key_exists(0, $parameters)) {
            // Load up the relevant block and grab its help output
            $hooks = find_all_hooks('systems', 'occle_commands');
            $hook_return = null;
            foreach (array_keys($hooks) as $hook) {
                if ($hook == $parameters[0]) {
                    require_code('hooks/systems/occle_commands/' . filter_naughty_harsh($hook));
                    $object = object_factory('Hook_occle_command_' . filter_naughty_harsh($hook), true);
                    if (is_null($object)) {
                        continue;
                    }
                    $hook_return = $object->run(array('help' => null), array(), $occle_fs);
                    break;
                }
            }

            if (!is_null($hook_return)) {
                return array($hook_return[0], $hook_return[1], $hook_return[2], $hook_return[3]);
            } else {
                return array('', '', '', do_lang('NO_HELP'));
            }
        } else {
            // Output a standard "how to use Occle" help page
            return array('window.open(unescape("' . urlencode(get_tutorial_url('occle')) . '"),"occle_window1","");', '', do_lang('SUCCESS'), '');
        }
    }
}
