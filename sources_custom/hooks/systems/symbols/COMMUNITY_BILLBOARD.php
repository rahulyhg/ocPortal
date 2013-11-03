<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		community_billboard
 */

class Hook_symbol_COMMUNITY_BILLBOARD
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		if (!addon_installed('community_billboard')) return '';

		require_css('community_billboard');

		$system=(mt_rand(0,1)==0);
		$_community_billboard=NULL;

		if ((!$system) || (get_option('system_community_billboard')==''))
		{
			$_community_billboard=persistent_cache_get('COMMUNITY_BILLBOARD');
			if ($_community_billboard===NULL)
			{
				$community_billboard=$GLOBALS['SITE_DB']->query_value_if_there('SELECT the_message FROM '.get_table_prefix().'community_billboard WHERE active_now=1 AND activation_time+days*60*60*24>'.strval(time()),true/*in case tablemissing*/);
				if ($community_billboard===NULL)
				{
					persistent_cache_set('COMMUNITY_BILLBOARD',false);
				} else
				{
					$_community_billboard=get_translated_tempcode($community_billboard);
					persistent_cache_set('COMMUNITY_BILLBOARD',$_community_billboard);
				}
			}
			if ($_community_billboard===false) $_community_billboard=NULL;
		}
		if ($_community_billboard===NULL)
		{
			$value=get_option('system_community_billboard');
		} else
		{
			$value=do_lang('_COMMUNITY_MESSAGE',$_community_billboard);
		}

		return $value;
	}

}