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
 * @package		galleries
 */

class Hook_choose_gallery
{

	/**
	 * Standard modular run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by Javascript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
	function run($id,$options,$default=NULL)
	{
		require_code('galleries');
		require_lang('galleries');

		$must_accept_images=array_key_exists('must_accept_images',$options)?$options['must_accept_images']:false;
		$must_accept_videos=array_key_exists('must_accept_videos',$options)?$options['must_accept_videos']:false;
		$must_accept_something=array_key_exists('must_accept_something',$options)?$options['must_accept_something']:false;
		$filter=array_key_exists('filter',$options)?$options['filter']:NULL;
		$purity=array_key_exists('purity',$options)?$options['purity']:false;
		$member_id=array_key_exists('member_id',$options)?$options['member_id']:NULL;
		$compound_list=array_key_exists('compound_list',$options)?$options['compound_list']:false;
		$addable_filter=array_key_exists('addable_filter',$options)?$options['addable_filter']:false;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;
		$stripped_id=($compound_list?preg_replace('#,.*$#','',$id):$id);
		$tree=get_gallery_tree(is_null($id)?'root':$stripped_id,'',NULL,true,$filter,false,false,$purity,$compound_list,is_null($id)?0:1,$member_id,$addable_filter,$editable_filter);

		if (!has_actual_page_access(NULL,'galleries')) $tree=$compound_list?array(array(),''):array();

		if ($compound_list)
		{
			list($tree,)=$tree;
		}

		$out='';
		for ($i=0;$i<count($tree);$i++)
		{
			$t=$tree[$i];

			$_id=$compound_list?$t['compound_list']:$t['id'];
			if ($stripped_id===$t['id'])
			{
				// Possible when we look under as a root
				if (array_key_exists('children',$t))
				{
					$tree=$t['children'];
					$i=0;
				}
				continue;
			}
			$title=$t['title'];
			if (is_object($title)) $title=@html_entity_decode(strip_tags($title->evaluate()),ENT_QUOTES,get_charset());
			$has_children=($t['child_count']!=0);
			$selectable=
				(($editable_filter!==true) || ($t['editable'])) && 
				(($addable_filter!==true) || ($t['addable'])) && 
				(((($t['accept_images']==1) || ($t['accept_videos']==1)) && ($t['is_member_synched']==0)) || (!$must_accept_something)) && 
				((($t['accept_videos']==1) && ($t['is_member_synched']==0)) || (!$must_accept_videos)) && 
				((($t['accept_images']==1) && ($t['is_member_synched']==0)) || (!$must_accept_images));

			if ((!$has_children) || (strpos($_id,'member_')!==false))
			{
				if (($editable_filter) && (!$t['editable'])) continue;
				if (($addable_filter) && (!$t['addable'])) continue;
			}

			$tag='category'; // category
			$out.='<'.$tag.' id="'.xmlentities($_id).'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="'.($selectable?'true':'false').'"></'.$tag.'>';

			// Mark parent cats for pre-expansion
			if ((!is_null($default)) && ($default!=''))
			{
				$cat=$default;
				while ((!is_null($cat)) && ($cat!=''))
				{
					$out.='<expand>'.$cat.'</expand>';
					$cat=$GLOBALS['SITE_DB']->query_value_null_ok('galleries','parent_id',array('name'=>$cat));
				}
			}
		}

		$tag='result'; // result
		return '<'.$tag.'>'.$out.'</'.$tag.'>';
	}

	/**
	 * Standard modular simple function for ajax-tree hooks. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return tempcode		The nice list
	 */
	function simple($id,$options,$it=NULL)
	{
		unset($id);

		$must_accept_images=array_key_exists('must_accept_images',$options)?$options['must_accept_images']:false;
		$must_accept_videos=array_key_exists('must_accept_videos',$options)?$options['must_accept_videos']:false;
		$filter=array_key_exists('filter',$options)?$options['filter']:NULL;
		$purity=array_key_exists('purity',$options)?$options['purity']:false;
		$member_id=array_key_exists('member_id',$options)?$options['member_id']:NULL;
		$compound_list=array_key_exists('compound_list',$options)?$options['compound_list']:false;
		$addable_filter=array_key_exists('addable_filter',$options)?$options['addable_filter']:false;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;

		require_code('galleries');

		return nice_get_gallery_tree($it,$filter,$must_accept_images,$must_accept_videos,$purity,$compound_list,$member_id,$addable_filter,$editable_filter);
	}

}


