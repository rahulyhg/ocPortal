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

class Hook_admin_themewizard_ocf_forum
{
	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function run()
	{
		return array(array('ocf_general/no_new_posts_redirect','ocf_general/new_posts_redirect','ocf_general/no_new_posts','ocf_general/new_posts','icons/14x14/ocf_topic_modifiers/involved','icons/28x28/ocf_topic_modifiers/involved',),array('pageitem/warn',));
	}
}


