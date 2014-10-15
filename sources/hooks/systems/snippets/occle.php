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

class Hook_occle
{
    /**
	 * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
    public function run()
    {
        if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        if (has_actual_page_access(get_member(),'admin_occle')) {
            require_code('occle');
            require_lang('occle');

            $title = get_screen_title('OCCLE');

            return do_template('OCCLE_MAIN',array(
                '_GUID' => '2f29170f4f8320a26fad66e0d0f52b7a',
                'COMMANDS' => '',
                'SUBMIT_URL' => build_url(array('page' => 'admin_occle'),'adminzone'),
                'PROMPT' => do_lang_tempcode('COMMAND_PROMPT',escape_html($GLOBALS['FORUM_DRIVER']->get_username(get_member()))),
            ));
        }

        return new ocp_tempcode();
    }
}
