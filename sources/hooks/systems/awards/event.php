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
 * @package		calendar
 */

class Hook_awards_event
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		$info=array();
		$info['connection']=$GLOBALS['SITE_DB'];
		$info['table']='calendar_events';
		$info['date_field']='e_add_date';
		$info['id_field']='id';
		$info['add_url']=(has_submit_permission('mid',get_member(),get_ip_address(),'cms_calendar'))?build_url(array('page'=>'cms_calendar','type'=>'ad'),get_module_zone('cms_calendar')):new ocp_tempcode();
		$info['category_field']='e_type';
		$info['parent_spec__table_name']='calendar_types';
		$info['parent_spec__parent_name']=NULL;
		$info['parent_spec__field_name']='id';
		$info['parent_field_name']='e_type';
		$info['submitter_field']='e_submitter';
		$info['id_is_string']=false;
		require_lang('calendar');
		$info['title']=do_lang_tempcode('EVENT');
		$info['validated_field']='validated';
		$info['category_is_string']=false;
		$info['archive_url']=build_url(array('page'=>'calendar'),get_module_zone('calendar'));
		$info['cms_page']='cms_calendar';
		$info['views_field']='e_views';
		$info['supports_custom_fields']=true;

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @return tempcode	Results
	 */
	function run($row,$zone)
	{
		require_css('calendar');

		$url=build_url(array('page'=>'calendar','type'=>'view','id'=>$row['id']),$zone);

		return do_template('CALENDAR_EVENT_BOX',array('TITLE'=>get_translated_text($row['e_title']),'SUMMARY'=>get_translated_tempcode($row,'e_content'),'URL'=>$url));
	}

}


