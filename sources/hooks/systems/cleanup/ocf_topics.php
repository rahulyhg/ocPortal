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
 * @package		ocf_forum
 */

class Hook_ocf_topics
{
    /**
	 * Find details about this cleanup hook.
	 *
	 * @return ?array	Map of cleanup hook info (NULL: hook is disabled).
	 */
    public function info()
    {
        if (get_forum_type() != 'ocf') {
            return NULL;
        } else {
            ocf_require_all_forum_stuff();
        }

        require_lang('ocf');

        $info = array();
        $info['title'] = do_lang_tempcode('FORUM_TOPICS');
        $info['description'] = do_lang_tempcode('DESCRIPTION_CACHE_TOPICS');
        $info['type'] = 'cache';

        return $info;
    }

    /**
	 * Run the cleanup hook action.
	 *
	 * @return tempcode	Results
	 */
    public function run()
    {
        if (get_forum_type() != 'ocf') {
            return new ocp_tempcode();
        }

        require_code('tasks');
        return call_user_func_array__long_task(do_lang('CACHE_TOPICS'),null,'ocf_topics_recache');
    }
}
