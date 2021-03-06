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
 * @package		downloads
 */

class Block_main_download_tease
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('zone');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(get_param_integer(\'max\',10),get_param_integer(\'start\',0),array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'downloads\'))';
		$info['ttl']=60*24;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_code('downloads');
		require_css('downloads');
		require_lang('downloads');

		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('downloads');

		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='max';

		$max=get_param_integer('max',10);
		if ($max<1) $max=1;
		$start=get_param_integer('start',0);

		$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('*'),array('validated'=>1),'ORDER BY num_downloads DESC',$max,$start);

		$content=new ocp_tempcode();
		foreach ($rows as $i=>$row)
		{
			if ($i!=0) $content->attach(do_template('BLOCK_SEPARATOR'));

			$content->attach(get_download_html($row,true,true,$zone));
		}

		$page_num=intval(floor(floatval($start)/floatval($max)))+1;
		$count=$GLOBALS['SITE_DB']->query_value('download_downloads','COUNT(*)',array('validated'=>1));
		$num_pages=intval(ceil(floatval($count)/floatval($max)));
		if ($num_pages==0) $page_num=0;

		$previous_url=($start==0)?new ocp_tempcode():build_url(array('page'=>'_SELF','start'=>$start-$max),'_SELF');
		$next_url=($page_num==$num_pages)?new ocp_tempcode():build_url(array('page'=>'_SELF','start'=>$start+$max),'_SELF');
		$browse=do_template('NEXT_BROWSER_BROWSE_NEXT',array('_GUID'=>'15ca70ec400629f67edefa869fb1f1a8','NEXT_LINK'=>$next_url,'PREVIOUS_LINK'=>$previous_url,'PAGE_NUM'=>integer_format($page_num),'NUM_PAGES'=>integer_format($num_pages)));

		return do_template('BLOCK_MAIN_DOWNLOAD_TEASE',array('_GUID'=>'a164e33c0b4ace4bae945c39f2f00ca9','CONTENT'=>$content,'BROWSE'=>$browse));
	}

}


