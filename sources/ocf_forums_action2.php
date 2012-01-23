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
 * @package		core_ocf
 */

/**
 * Edit a forum category.
 *
 * @param  AUTO_LINK		The ID of the forum category we are editing.
 * @param  SHORT_TEXT	The title of the forum category.
 * @param  SHORT_TEXT	The description of the forum category.
 * @param  BINARY			Whether the forum category will be shown expanded by default (as opposed to contracted, where contained forums will not be shown until expansion).
 */
function ocf_edit_category($category_id,$title,$description,$expanded_by_default)
{
	$old_title=$GLOBALS['FORUM_DB']->query_value('f_categories','c_title',array('id'=>$category_id));
	
	$GLOBALS['FORUM_DB']->query_update('f_categories',array(
		'c_title'=>$title,
		'c_description'=>$description,
		'c_expanded_by_default'=>$expanded_by_default
	),array('id'=>$category_id),'',1);

	if ($old_title!=$title)
	{
		$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_categories','c_title',array('c_title'=>$old_title));
		if (is_null($test)) // Ok, so we know there was only 1 forum named that and now it is gone
			$GLOBALS['FORUM_DB']->query_update('config',array('config_value'=>$title),array('the_type'=>'category','config_value'=>$old_title));
	}

	log_it('EDIT_FORUM_CATEGORY',strval($category_id),$title);
}

/**
 * Delete a forum category.
 *
 * @param  AUTO_LINK  The ID of the forum category we are editing.
 * @param  AUTO_LINK  The ID of the category that we will move all the contained forum to.
 */
function ocf_delete_category($category_id,$target_category_id)
{
	$title=$GLOBALS['FORUM_DB']->query_value_null_ok('f_categories','c_title',array('id'=>$category_id));
	if (is_null($title)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$GLOBALS['FORUM_DB']->query_update('f_forums',array('f_category_id'=>$target_category_id),array('f_category_id'=>$category_id));
	$GLOBALS['FORUM_DB']->query_delete('f_categories',array('id'=>$category_id),'',1);
	log_it('DELETE_FORUM_CATEGORY',strval($category_id),$title);
}

/**
 * Edit a forum.
 *
 * @param  AUTO_LINK		The ID of the forum we are editing.
 * @param  SHORT_TEXT	The name of the forum.
 * @param  SHORT_TEXT	The description for the forum.
 * @param  AUTO_LINK		What forum category the forum will be filed with.
 * @param  ?AUTO_LINK	The ID of the parent forum (NULL: this is the root forum).
 * @param  integer		The position of this forum relative to other forums viewable on the same screen (if parent forum hasn't specified automatic ordering).
 * @param  BINARY			Whether post counts will be incremented if members post in the forum.
 * @param  BINARY			Whether the ordering of subforums is done automatically, alphabetically).
 * @param  LONG_TEXT		The question that is shown for newbies to the forum (blank: none).
 * @param  SHORT_TEXT	The answer to the question (blank: no specific answer.. if there's a 'question', it just requires a click-through).
 * @param  SHORT_TEXT	Either blank for no redirection, the ID of another forum we are mirroring, or a URL to redirect to.
 * @param  ID_TEXT		The order the topics are shown in, by default.
 * @param  boolean		Whether to force forum rules to be re-agreed to, if they've just been changed.
 */
function ocf_edit_forum($forum_id,$name,$description,$category_id,$new_parent,$position,$post_count_increment,$order_sub_alpha,$intro_question,$intro_answer,$redirection='',$order='last_post',$reset_intro_acceptance=false)
{
	if ($category_id==-1) $category_id=NULL;
	if ($new_parent==-1) $new_parent=NULL;

	require_code('urls2');
	suggest_new_idmoniker_for('forumview','misc',strval($forum_id),$name);

	if ((!is_null($category_id)) && ($category_id!=INTEGER_MAGIC_NULL)) ocf_ensure_category_exists($category_id);
	if ((!is_null($new_parent)) && ($new_parent!=INTEGER_MAGIC_NULL)) ocf_ensure_forum_exists($new_parent);

	$forum_info=$GLOBALS['FORUM_DB']->query_select('f_forums',array('*'),array('id'=>$forum_id),'',1);
	if (!array_key_exists(0,$forum_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$old_parent=$forum_info[0]['f_parent_forum'];
	$old_name=$forum_info[0]['f_name'];

	if ($new_parent==$forum_id) warn_exit(do_lang_tempcode('FORUM_CANNOT_BE_OWN_PARENT'));

	if (($reset_intro_acceptance) && (trim(get_translated_text($forum_info[0]['f_intro_question'],$GLOBALS['FORUM_DB']))!=trim($intro_question)) && ($intro_question!=STRING_MAGIC_NULL))
	{
		$GLOBALS['FORUM_DB']->query_delete('f_forum_intro_ip',array('i_forum_id'=>$forum_id));
		$GLOBALS['FORUM_DB']->query_delete('f_forum_intro_member',array('i_forum_id'=>$forum_id));
	}

	$GLOBALS['FORUM_DB']->query_update('f_forums',array(
		'f_name'=>$name,
		'f_description'=>lang_remap($forum_info[0]['f_description'],$description,$GLOBALS['FORUM_DB']),
		'f_category_id'=>$category_id,
		'f_parent_forum'=>$new_parent,
		'f_position'=>$position,
		'f_order_sub_alpha'=>$order_sub_alpha,
		'f_intro_question'=>lang_remap($forum_info[0]['f_intro_question'],$intro_question,$GLOBALS['FORUM_DB']),
		'f_intro_answer'=>$intro_answer,
		'f_post_count_increment'=>$post_count_increment,
		'f_redirection'=>$redirection,
		'f_order'=>$order,
	),array('id'=>$forum_id),'',1);

	if ($old_name!=$name)
	{
		$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_forums','f_name',array('f_name'=>$old_name));
		if (is_null($test)) // Ok, so we know there was only 1 forum named that and now it is gone
			$GLOBALS['FORUM_DB']->query_update('config',array('config_value'=>$name),array('the_type'=>'forum','config_value'=>$old_name));
	}

	if (($old_parent!=$new_parent) && ($new_parent!=INTEGER_MAGIC_NULL))
	{
		// Recalc stats
		require_code('ocf_posts_action2');
		$num_topics_forum=$forum_info[0]['f_cache_num_topics']; // This is valid, because we move all this forums subforums too
		$num_posts_forum=$forum_info[0]['f_cache_num_posts'];
		if (!is_null($old_parent))
			ocf_force_update_forum_cacheing($old_parent,-$num_topics_forum,-$num_posts_forum);
		if (!is_null($new_parent))
			ocf_force_update_forum_cacheing($new_parent,$num_topics_forum,$num_posts_forum);
	}

	log_it('EDIT_FORUM',strval($forum_id),$name);
}

/**
 * Delete a forum.
 *
 * @param  AUTO_LINK		The ID of the forum we are deleting.
 * @param  AUTO_LINK		The ID of the forum that topics will be moved to.
 * @param  BINARY			Whether to delete topics instead of moving them to the target forum.
 */
function ocf_delete_forum($forum_id,$target_forum_id,$delete_topics=0)
{
	if ($forum_id==db_get_first_id()) warn_exit(do_lang_tempcode('CANNOT_DELETE_ROOT_FORUM'));
	require_code('ocf_topics_action');
	require_code('ocf_topics_action2');
	if ($delete_topics==0)
	{
		ocf_move_topics($forum_id,$target_forum_id);
	} else
	{
		$rows=$GLOBALS['FORUM_DB']->query_select('f_topics',array('id'),array('t_forum_id'=>$forum_id));
		foreach ($rows as $row)
		{
			ocf_delete_topic($row['id'],'');
		}
	}

	$forum_info=$GLOBALS['FORUM_DB']->query_select('f_forums',array('*'),array('id'=>$forum_id),'',1);
	if (!array_key_exists(0,$forum_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	delete_lang($forum_info[0]['f_description'],$GLOBALS['FORUM_DB']);
	delete_lang($forum_info[0]['f_intro_question'],$GLOBALS['FORUM_DB']);

	$name=$GLOBALS['FORUM_DB']->query_value('f_forums','f_name',array('id'=>$forum_id));
	$GLOBALS['FORUM_DB']->query_update('f_multi_moderations',array('mm_move_to'=>NULL),array('mm_move_to'=>$forum_id));
	$GLOBALS['FORUM_DB']->query_update('f_forums',array('f_parent_forum'=>db_get_first_id()),array('f_parent_forum'=>$forum_id));
	$GLOBALS['FORUM_DB']->query_delete('f_forums',array('id'=>$forum_id),'',1);
	$GLOBALS['FORUM_DB']->query_delete('group_category_access',array('module_the_name'=>'forums','category_name'=>strval($forum_id)));
	$GLOBALS['FORUM_DB']->query_delete('gsp',array('module_the_name'=>'forums','category_name'=>strval($forum_id)));
	require_code('notifications');
	delete_all_notifications_on('ocf_forum',strval($forum_id));
	$GLOBALS['FORUM_DB']->query_delete('f_forum_intro_member',array('i_forum_id'=>$forum_id));
	$GLOBALS['FORUM_DB']->query_delete('f_forum_intro_ip',array('i_forum_id'=>$forum_id));

	log_it('DELETE_FORUM',strval($forum_id),$name);
}

/**
 * Mark all recent topics in a certain forum as read for the current member.
 *
 * @param  AUTO_LINK		The ID of the forum.
 */
function ocf_ping_forum_read_all($forum_id)
{
	$or_list=ocf_get_all_subordinate_forums($forum_id,'t_forum_id');
	if ($or_list=='') return;
	$topics=$GLOBALS['FORUM_DB']->query('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE ('.$or_list.') AND t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))));
	$member_id=get_member();
	$or_list='';
	foreach ($topics as $topic)
	{
		if ($or_list!='') $or_list.=' OR ';
		$or_list.='l_topic_id='.strval((integer)$topic['id']);
	}
	if ($or_list=='') return;
	$GLOBALS['FORUM_DB']->query('DELETE FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs WHERE l_member_id='.strval((integer)$member_id).' AND ('.$or_list.')');
	$mega_insert=array('l_member_id'=>array(),'l_topic_id'=>array(),'l_time'=>array());
	foreach ($topics as $topic)
	{
		$mega_insert['l_member_id'][]=$member_id;
		$mega_insert['l_topic_id'][]=$topic['id'];
		$mega_insert['l_time'][]=time();
	}
	$GLOBALS['FORUM_DB']->query_insert('f_read_logs',$mega_insert);
}

/**
 * Mark all recent topics in a certain forum as unread for the current member.
 *
 * @param  AUTO_LINK		The ID of the forum.
 */
function ocf_ping_forum_unread_all($forum_id)
{
	$or_list=ocf_get_all_subordinate_forums($forum_id,'t_forum_id');
	$topics=$GLOBALS['FORUM_DB']->query('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE ('.$or_list.') AND t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))));
	$or_list_2='';
	foreach ($topics as $topic)
	{
		if ($or_list_2!='') $or_list_2.=' OR ';
		$or_list_2.='l_topic_id='.strval((integer)$topic['id']);
	}
	if ($or_list_2=='') return;
	$GLOBALS['FORUM_DB']->query('DELETE FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs WHERE '.$or_list_2);
}

/**
 * Bomb out if the specified forum category doesn't exist.
 *
 * @param  AUTO_LINK		The ID of the category.
 */
function ocf_ensure_category_exists($category_id)
{
	$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_categories','id',array('id'=>$category_id));
	if (is_null($test))
	{
		warn_exit(do_lang_tempcode('CAT_NOT_FOUND',strval($category_id)));
	}
}

/**
 * Bomb out if the specified forum doesn't exist.
 *
 * @param  AUTO_LINK		The ID of the forum.
 * @return SHORT_TEXT	The name of the forum.
 */
function ocf_ensure_forum_exists($forum_id)
{
	$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_forums','f_name',array('id'=>$forum_id));
	if (is_null($test))
	{
		warn_exit(do_lang_tempcode('FORUM_NOT_FOUND',strval($forum_id)));
	}
	return $test;
}

