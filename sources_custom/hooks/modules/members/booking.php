<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		booking
 */

class Hook_members_booking
{

	/**
	 * Standard modular run function.
	 *
	 * @param  MEMBER		The ID of the member we are getting link hooks for
	 * @return array		List of tuples for results. Each tuple is: type,title,url
	 */
	function run($member_id)
	{
		if (!has_actual_page_access(get_member(),'cms_booking')) return array();

		require_lang('booking');
		require_code('booking');
		require_code('booking2');

		$zone=get_module_zone('cms_booking');

		$request=get_member_booking_request($member_id);

		$links=array();

		foreach ($request as $i=>$r)
		{
			$from=get_timezoned_date(mktime(0,0,0,$r['start_month'],$r['start_day'],$r['start_year']),false);
			$to=get_timezoned_date(mktime(0,0,0,$r['end_month'],$r['end_day'],$r['end_year']),false);
			$links[]=array(
				'content',
				do_lang_tempcode('BOOKING_EDIT',escape_html($from),escape_html($to),get_translated_tempcode($GLOBALS['SITE_DB']->query_value('bookable','title',array('id'=>$r['bookable_id'])))),
				build_url(array('page'=>'cms_booking','type'=>'_eb','id'=>strval($member_id).'_'.strval($i)),$zone),
			);
		}

		return $links;
	}

}
