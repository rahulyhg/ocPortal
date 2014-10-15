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
 * @package		random_quotes
 */

class Hook_sw_random_quotes
{
    /**
	 * Run function for blocks in the setup wizard.
	 *
	 * @return array		Map of block names, to display types.
	 */
    public function get_blocks()
    {
        if (!addon_installed('random_quotes')) {
            return array();
        }

        return array(array('main_quotes' => array('YES','NO')),array());
    }
}
