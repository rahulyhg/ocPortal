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
 * @package		ocf_forum
 */

class Hook_rss_ocf_unread_topics
{

	/**
	 * Standard modular run function for RSS hooks.
	 *
	 * @param  string			A list of categories we accept from
	 * @param  TIME			Cutoff time, before which we do not show results from
	 * @param  string			Prefix that represents the template set we use
	 * @set    RSS_ ATOM_
	 * @param  string			The standard format of date to use for the syndication type represented in the prefix
	 * @param  integer		The maximum number of entries to return, ordering by date
	 * @return ?array			A pair: The main syndication section, and a title (NULL: error)
	 */
	function run($_filters,$cutoff,$prefix,$date_string,$max)
	{
		if (get_forum_type()!='ocf') return NULL;
		if (!has_actual_page_access(get_member(),'forumview')) return NULL;
		if (is_guest()) return NULL;

		$condition='l_time<t_cache_last_time OR (l_time IS NULL AND t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))).')';
		$query=' FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics top LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (top.id=l.l_topic_id AND l.l_member_id='.strval((integer)get_member()).') LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND top.t_cache_first_post=t.id WHERE ('.$condition.') AND t_forum_id IS NOT NULL '.((!has_specific_permission(get_member(),'see_unvalidated'))?' AND t_validated=1 ':'').' ORDER BY t_cache_last_time DESC';
		$rows=$GLOBALS['FORUM_DB']->query('SELECT *,top.id AS t_id '.$query,$max);
		$categories=collapse_2d_complexity('id','f_name',$GLOBALS['FORUM_DB']->query('SELECT id,f_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE f_cache_num_posts>0'));

		$content=new ocp_tempcode();
		foreach ($rows as $row)
		{
			if (((!is_null($row['t_forum_id'])) || ($row['t_pt_to']==get_member())) && (has_category_access(get_member(),'forums',strval($row['t_forum_id']))))
			{
				$id=strval($row['id']);
				$author=$row['t_cache_first_username'];

				$news_date=date($date_string,$row['t_cache_first_time']);
				$edit_date=date($date_string,$row['t_cache_last_time']);
				if ($edit_date==$news_date) $edit_date='';

				$news_title=xmlentities($row['t_cache_first_title']);
				$_summary=get_translated_tempcode($row,'t_cache_first_post',$GLOBALS['FORUM_DB']);
				$summary=xmlentities($_summary->evaluate());
				$news='';

				$category=array_key_exists($row['t_forum_id'],$categories)?$categories[$row['t_forum_id']]:do_lang('NA');
				$category_raw=strval($row['t_forum_id']);

				$view_url=build_url(array('page'=>'topicview','id'=>$row['t_id']),get_module_zone('topicview'));

				if ($prefix=='RSS_')
				{
					$if_comments=do_template('RSS_ENTRY_COMMENTS',array('_GUID'=>'517e4d1be810446bda57d8632dadb4d6','COMMENT_URL'=>$view_url,'ID'=>strval($row['t_id'])));
				} else $if_comments=new ocp_tempcode();

				$content->attach(do_template($prefix.'ENTRY',array('VIEW_URL'=>$view_url,'SUMMARY'=>$summary,'EDIT_DATE'=>$edit_date,'IF_COMMENTS'=>$if_comments,'TITLE'=>$news_title,'CATEGORY_RAW'=>$category_raw,'CATEGORY'=>$category,'AUTHOR'=>$author,'ID'=>$id,'NEWS'=>$news,'DATE'=>$news_date)));
			}
		}

		require_lang('ocf');
		return array($content,do_lang('TOPICS_UNREAD'));
	}

}


