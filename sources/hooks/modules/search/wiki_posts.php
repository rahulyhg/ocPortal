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
 * @package		wiki
 */

class Hook_search_wiki_posts
{
	/**
	 * Standard modular info function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @return ?array		Map of module info (NULL: module is disabled).
	 */
	function info($check_permissions=true)
	{
		if (!module_installed('wiki')) return NULL;

		if ($check_permissions)
		{
			if (!has_actual_page_access(get_member(),'wiki')) return NULL;
		}

		if ($GLOBALS['SITE_DB']->query_select_value('wiki_posts','COUNT(*)')==0) return NULL;

		require_lang('wiki');

		$info=array();
		$info['lang']=do_lang_tempcode('WIKI_POSTS');
		$info['default']=false;
		$info['category']='page_id';
		$info['integer_category']=true;

		$info['permissions']=array(
			array(
				'type'=>'zone',
				'zone_name'=>get_module_zone('wiki'),
			),
			array(
				'type'=>'page',
				'zone_name'=>get_module_zone('wiki'),
				'page_name'=>'wiki',
			),
		);

		return $info;
	}

	/**
	 * Get details for an ajax-tree-list of entries for the content covered by this search hook.
	 *
	 * @return array			A pair: the hook, and the options
	 */
	function ajax_tree()
	{
		return array('choose_wiki_page',array('compound_list'=>true));
	}

	/**
	 * Standard modular run function for search results.
	 *
	 * @param  string			Search string
	 * @param  boolean		Whether to only do a META (tags) search
	 * @param  ID_TEXT		Order direction
	 * @param  integer		Start position in total results
	 * @param  integer		Maximum results to return in total
	 * @param  boolean		Whether only to search titles (as opposed to both titles and content)
	 * @param  string			Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
	 * @param  SHORT_TEXT	Username/Author to match for
	 * @param  ?MEMBER		Member-ID to match for (NULL: unknown)
	 * @param  TIME			Cutoff date
	 * @param  string			The sort type (gets remapped to a field in this function)
	 * @set    title add_date
	 * @param  integer		Limit to this number of results
	 * @param  string			What kind of boolean search to do
	 * @set    or and
	 * @param  string			Where constraints known by the main search code (SQL query fragment)
	 * @param  string			Comma-separated list of categories to search under
	 * @param  boolean		Whether it is a boolean search
	 * @return array			List of maps (template, orderer)
	 */
	function run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$limit_to,$boolean_operator,$where_clause,$search_under,$boolean_search)
	{
		$remapped_orderer='';
		switch ($sort)
		{
			case 'average_rating':
			case 'compound_rating':
				$remapped_orderer=$sort.':wiki_post:id';
				break;

			case 'title':
				$remapped_orderer='page_id'; // No good fit
				break;

			case 'add_date':
				$remapped_orderer='date_and_time';
				break;
		}

		require_code('wiki');
		require_lang('wiki');

		// Calculate our where clause (search)
		$sq=build_search_submitter_clauses('member_id',$author_id,$author);
		if (is_null($sq)) return array(); else $where_clause.=$sq;
		if (!is_null($cutoff))
		{
			$where_clause.=' AND ';
			$where_clause.='date_and_time>'.strval($cutoff);
		}

		if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')))
		{
			$where_clause.=' AND ';
			$where_clause.='validated=1';
		}

		// Calculate and perform query
		$rows=get_search_rows(NULL,NULL,$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'wiki_posts r',array(''=>'','r.the_message'=>'LONG_TRANS__COMCODE'),$where_clause,$content_where,$remapped_orderer,'r.*',NULL,'wiki_page','page_id');

		$out=array();
		foreach ($rows as $i=>$row)
		{
			$out[$i]['data']=$row;
			unset($rows[$i]);
			if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (strpos($remapped_orderer,'_rating:')!==false) $out[$i]['orderer']=$row[$remapped_orderer];
		}

		return $out;
	}

	/**
	 * Standard modular run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return tempcode	The output
	 */
	function render($row)
	{
		return render_wiki_post_box($row);
	}
}


