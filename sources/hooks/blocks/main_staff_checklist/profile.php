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
 * @package		core_adminzone_dashboard
 */

class Hook_checklist_profile
{
	/**
	 * Standard modular run function.
	 *
	 * @return array		An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
	 */
	function run()
	{
		if (get_forum_type()=='none') return array();

		$admins=$GLOBALS['FORUM_DRIVER']->member_group_query($GLOBALS['FORUM_DRIVER']->get_super_admin_groups());
		if (($GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member())=='') && (count($admins)==1))
		{
			$todo=1;
		} else
		{
			$todo=0;
		}
		$_status=($todo==1)?do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_0'):do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_1');
		$url=$GLOBALS['FORUM_DRIVER']->member_home_url(get_member());
		$tpl=do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM',array('_GUID'=>'276b29a1dac30addf9459fd960a260cd','URL'=>'','STATUS'=>$_status,'TASK'=>urlise_lang(do_lang('NAG_SETUP_PROFILE'),$url)));
		return array(array($tpl,NULL,$todo,NULL));
	}
}


