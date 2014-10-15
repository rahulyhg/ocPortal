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
 * @package		core_menus
 */

class Hook_management_menu
{
    /**
	 * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
    public function run()
    {
        if (has_zone_access(get_member(),'adminzone')) {
            require_code('menus');
            return build_stored_menu('popup','_management');
        }
        return new ocp_tempcode();
    }
}
