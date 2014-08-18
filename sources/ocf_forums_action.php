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
 * @package		core_ocf
 */

/**
 * Add a forum grouping.
 *
 * @param  SHORT_TEXT The title of the forum grouping.
 * @param  SHORT_TEXT The description of the forum grouping.
 * @param  BINARY	Whether the forum grouping will be shown expanded by default (as opposed to contracted, where contained forums will not be shown until expansion).
 * @return AUTO_LINK  The ID of the forum grouping just added.
 */
function ocf_make_forum_grouping($title,$description,$expanded_by_default=1)
{
	require_code('global4');
	prevent_double_submit('ADD_FORUM_GROUPING',NULL,$title);

	$forum_grouping_id=$GLOBALS['FORUM_DB']->query_insert('f_forum_groupings',array(
		'c_title'=>$title,
		'c_description'=>$description,
		'c_expanded_by_default'=>$expanded_by_default
	),true);

	log_it('ADD_FORUM_GROUPING',strval($forum_grouping_id),$title);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('forum_grouping',strval($forum_grouping_id),NULL,NULL,true);
	}

	return $forum_grouping_id;
}

/**
 * Make a forum.
 *
 * @param  SHORT_TEXT 	The name of the forum.
 * @param  SHORT_TEXT 	The description for the forum.
 * @param  ?AUTO_LINK	What forum grouping the forum will be filed with (NULL: this is the root forum).
 * @param  ?array			Permission map (NULL: do it the standard way, outside of this function). This parameter is for import/compatibility only and works upon an emulation of 'access levels' (ala ocPortal 2.5/2.6), and it is recommended to use the normal crud_module functionality for permissions setting.
 * @param  ?AUTO_LINK 	The ID of the parent forum (NULL: this is the root forum).
 * @param  integer		The position of this forum relative to other forums viewable on the same screen (if parent forum hasn't specified automatic ordering).
 * @param  BINARY			Whether post counts will be incremented if members post in the forum.
 * @param  BINARY			Whether the ordering of subforums is done automatically, alphabetically).
 * @param  LONG_TEXT		The question that is shown for newbies to the forum (blank: none).
 * @param  SHORT_TEXT	The answer to the question (blank: no specific answer.. if there's a 'question', it just requires a click-through).
 * @param  SHORT_TEXT	Either blank for no redirection, the ID of another forum we are mirroring, or a URL to redirect to.
 * @param  ID_TEXT		The order the topics are shown in, by default.
 * @param  BINARY			Whether the forum is threaded.
 * @return AUTO_LINK		The ID of the newly created forum.
 */
function ocf_make_forum($name,$description,$forum_grouping_id,$access_mapping,$parent_forum,$position=1,$post_count_increment=1,$order_sub_alpha=0,$intro_question='',$intro_answer='',$redirection='',$order='last_post',$is_threaded=0)
{
	require_code('global4');
	prevent_double_submit('ADD_FORUM',NULL,$name);

	if ($forum_grouping_id==-1) $forum_grouping_id=NULL;
	if ($parent_forum==-1) $parent_forum=NULL;

	if (!get_mass_import_mode())
	{
		if ((!is_null($forum_grouping_id)) && (function_exists('ocf_ensure_forum_grouping_exists'))) ocf_ensure_forum_grouping_exists($forum_grouping_id);
		if ((!is_null($parent_forum)) && (function_exists('ocf_ensure_forum_exists'))) ocf_ensure_forum_exists($parent_forum);
	}

	$map=array(
		'f_name'=>$name,
		'f_forum_grouping_id'=>$forum_grouping_id,
		'f_parent_forum'=>$parent_forum,
		'f_position'=>$position,
		'f_order_sub_alpha'=>$order_sub_alpha,
		'f_post_count_increment'=>$post_count_increment,
		'f_intro_answer'=>$intro_answer,
		'f_cache_num_topics'=>0,
		'f_cache_num_posts'=>0,
		'f_cache_last_topic_id'=>NULL,
		'f_cache_last_forum_id'=>NULL,
		'f_cache_last_title'=>'',
		'f_cache_last_time'=>NULL,
		'f_cache_last_username'=>'',
		'f_cache_last_member_id'=>NULL,
		'f_redirection'=>$redirection,
		'f_order'=>$order,
		'f_is_threaded'=>$is_threaded,
	);
	$map+=insert_lang_comcode('f_description',$description,2,$GLOBALS['FORUM_DB']);
	$map+=insert_lang_comcode('f_intro_question',$intro_question,3,$GLOBALS['FORUM_DB']);
	$forum_id=$GLOBALS['FORUM_DB']->query_insert('f_forums',$map,true);

	// Set permissions
	if (!is_null($access_mapping))
	{
		$groups=$GLOBALS['OCF_DRIVER']->get_usergroup_list(false,true);
		foreach (array_keys($groups) as $group_id)
		{
			$level=0; // No-access
			if (array_key_exists($group_id,$access_mapping)) $level=$access_mapping[$group_id];
			if ($level>=1) // Access
			{
				$GLOBALS['FORUM_DB']->query_insert('group_category_access',array(
					'module_the_name'=>'forums',
					'category_name'=>strval($forum_id),
					'group_id'=>$group_id
				));

				if ($level==1) // May not post - so specifically override to say this
				{
					$GLOBALS['FORUM_DB']->query_insert('group_privileges',array('privilege'=>'submit_lowrange_content','group_id'=>$group_id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>0));
					$GLOBALS['FORUM_DB']->query_insert('group_privileges',array('privilege'=>'submit_midrange_content','group_id'=>$group_id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>0));
				}
				if ($level>=3)
				{
					$GLOBALS['FORUM_DB']->query_insert('group_privileges',array('privilege'=>'bypass_validation_lowrange_content','group_id'=>$group_id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
				}
				if ($level>=4)
				{
					$GLOBALS['FORUM_DB']->query_insert('group_privileges',array('privilege'=>'bypass_validation_midrange_content','group_id'=>$group_id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
				}

				// 2=May post, [3=May post instantly , 4=May start topics instantly , 5=Moderator  --  these ones will not be treated specially, so as to avoid overriding permissions unnecessary - let the admins configure it optimally manually]
			}
		}
	}

	log_it('ADD_FORUM',strval($forum_id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('forum',strval($forum_id),NULL,NULL,true);
	}

	if ((!is_null($parent_forum)) && (!running_script('install')))
	{
		require_code('notifications2');
		copy_notifications_to_new_child('ocf_topic',strval($parent_forum),strval($forum_id));
	}

	require_code('member_mentions');
	dispatch_member_mention_notifications('forum',strval($forum_id));

	return $forum_id;
}

