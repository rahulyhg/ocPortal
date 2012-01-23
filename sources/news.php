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
 * @package		news
 */


/**
 * Add a news category of the specified details.
 *
 * @param  SHORT_TEXT	The news category title
 * @param  ID_TEXT		The theme image ID of the picture to use for the news category
 * @param  LONG_TEXT		Notes for the news category
 * @param  ?MEMBER		The owner (NULL: public)
 * @param  ?AUTO_LINK	Force an ID (NULL: don't force an ID)
 * @return AUTO_LINK		The ID of our new news category
 */
function add_news_category($title,$img,$notes,$owner=NULL,$id=NULL)
{
	$map=array('nc_title'=>insert_lang($title,1),'nc_img'=>$img,'notes'=>$notes,'nc_owner'=>$owner);
	if (!is_null($id)) $map['id']=$id;
	$id=$GLOBALS['SITE_DB']->query_insert('news_categories',$map,true);

	log_it('ADD_NEWS_CATEGORY',strval($id),$title);

	decache('side_news_categories');

	return $id;
}

/**
 * Edit a news category.
 *
 * @param  AUTO_LINK			The news category to edit
 * @param  ?SHORT_TEXT		The title (NULL: keep as-is)
 * @param  ?SHORT_TEXT		The image (NULL: keep as-is)
 * @param  ?LONG_TEXT		The notes (NULL: keep as-is)
 * @param  ?MEMBER			The owner (NULL: public)
*/
function edit_news_category($id,$title,$img,$notes,$owner=NULL)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('news_categories',array('nc_title','nc_img','notes'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	require_code('urls2');
	suggest_new_idmoniker_for('news','misc',strval($id),$title);

	log_it('EDIT_NEWS_CATEGORY',strval($id),$title);

	if (is_null($title)) $title=get_translated_text($myrow['nc_title']);
	if (is_null($img)) $img=$myrow['nc_img'];
	if (is_null($notes)) $notes=$myrow['notes'];

	$GLOBALS['SITE_DB']->query_update('news_categories',array('nc_title'=>lang_remap($myrow['nc_title'],$title),'nc_img'=>$img,'notes'=>$notes,'nc_owner'=>$owner),array('id'=>$id),'',1);

	require_code('themes2');
	tidy_theme_img_code($img,$myrow['nc_img'],'news_categories','nc_img');

	decache('main_news');
	decache('side_news');
	decache('side_news_archive');
	decache('bottom_news');
	decache('side_news_categories');
}

/**
 * Delete a news category.
 *
 * @param  AUTO_LINK		The news category to delete
 */
function delete_news_category($id)
{
	$rows=$GLOBALS['SITE_DB']->query_select('news_categories',array('nc_title','nc_img'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];

	$min=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT MIN(id) FROM '.get_table_prefix().'news_categories WHERE id<>'.strval((integer)$id));
	if (is_null($min)) 
	{
		warn_exit(do_lang_tempcode('YOU_MUST_KEEP_ONE_NEWS_CAT'));
	}

	log_it('DELETE_NEWS_CATEGORY',strval($id),get_translated_text($myrow['nc_title']));

	delete_lang($myrow['nc_title']);

	$GLOBALS['SITE_DB']->query_update('news',array('news_category'=>$min),array('news_category'=>$id));
	$GLOBALS['SITE_DB']->query_delete('news_categories',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('news_category_entries',array('news_entry_category'=>$id));

	$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'news','category_name'=>strval($id)));
	$GLOBALS['SITE_DB']->query_delete('gsp',array('module_the_name'=>'news','category_name'=>strval($id)));

	require_code('themes2');
	tidy_theme_img_code(NULL,$myrow['nc_img'],'news_categories','nc_img');

	decache('side_news_categories');
}

/**
 * Adds a news entry to the database, and send out the news to any RSS cloud listeners.
 *
 * @param  SHORT_TEXT		The news title
 * @param  LONG_TEXT			The news summary (or if not an article, the full news)
 * @param  ?ID_TEXT			The news author (possibly, a link to an existing author in the system, but does not need to be) (NULL: current username)
 * @param  BINARY				Whether the news has been validated
 * @param  BINARY				Whether the news may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the news may have trackbacks
 * @param  LONG_TEXT			Notes for the news
 * @param  LONG_TEXT			The news entry (blank means no entry)
 * @param  ?AUTO_LINK		The primary news category (NULL: personal)
 * @param  ?array				The IDs of the news categories that this is in (NULL: none)
 * @param  ?TIME				The time of submission (NULL: now)
 * @param  ?MEMBER			The news submitter (NULL: current member)
 * @param  integer			The number of views the article has had
 * @param  ?TIME				The edit date (NULL: never)
 * @param  ?AUTO_LINK		Force an ID (NULL: don't force an ID)
 * @param  URLPATH			URL to the image for the news entry (blank: use cat image)
 * @return AUTO_LINK			The ID of the news just added
 */
function add_news($title,$news,$author=NULL,$validated=1,$allow_rating=1,$allow_comments=1,$allow_trackbacks=1,$notes='',$news_article='',$main_news_category=NULL,$news_category=NULL,$time=NULL,$submitter=NULL,$views=0,$edit_date=NULL,$id=NULL,$image='')
{
	if (is_null($author)) $author=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
	if (is_null($news_category)) $news_category=array();
	if (is_null($time)) $time=time();
	if (is_null($submitter)) $submitter=get_member();
	$already_created_personal_category=false;

	require_code('comcode_check');
	check_comcode($news_article,NULL,false,NULL,true);

	if (is_null($main_news_category))
	{
		$main_news_category_id=$GLOBALS['SITE_DB']->query_value_null_ok('news_categories','id',array('nc_owner'=>$submitter));
		if (is_null($main_news_category_id))
		{
			if (!has_specific_permission(get_member(),'have_personal_category','cms_news')) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));

			$p_nc_title=insert_lang(do_lang('MEMBER_CATEGORY',$GLOBALS['FORUM_DRIVER']->get_username($submitter)),2);

			$main_news_category_id=$GLOBALS['SITE_DB']->query_insert('news_categories',array('nc_title'=>$p_nc_title,'nc_img'=>'newscats/community','notes'=>'','nc_owner'=>$submitter),true);
			$already_created_personal_category=true;

			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

			foreach (array_keys($groups) as $group_id)
				$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'news','category_name'=>strval($main_news_category_id),'group_id'=>$group_id));
		}
	}
	else $main_news_category_id=$main_news_category;

	if (!addon_installed('unvalidated')) $validated=1;
	$map=array('news_image'=>$image,'edit_date'=>$edit_date,'news_category'=>$main_news_category_id,'news_views'=>$views,'news_article'=>0,'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'submitter'=>$submitter,'validated'=>$validated,'date_and_time'=>$time,'title'=>insert_lang_comcode($title,1),'news'=>insert_lang_comcode($news,1),'author'=>$author);
	if (!is_null($id)) $map['id']=$id;
	$id=$GLOBALS['SITE_DB']->query_insert('news',$map,true);

	if (!is_null($news_category))
	{
		foreach ($news_category as $value)
		{
			if ((is_null($value)) && (!$already_created_personal_category))
			{
				$p_nc_title=insert_lang(do_lang('MEMBER_CATEGORY',$GLOBALS['FORUM_DRIVER']->get_username($submitter)),2);
				$news_category_id=$GLOBALS['SITE_DB']->query_insert('news_categories',array('nc_title'=>$p_nc_title,'nc_img'=>'newscats/community','notes'=>'','nc_owner'=>$submitter),true);

				$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

				foreach (array_keys($groups) as $group_id)
					$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'news','category_name'=>strval($news_category_id),'group_id'=>$group_id));
			}
			else $news_category_id=$value;

			if (is_null($news_category_id)) continue; // Double selected

			$GLOBALS['SITE_DB']->query_insert('news_category_entries',array('news_entry'=>$id,'news_entry_category'=>$news_category_id));
		}
	}

	require_code('attachments2');
	$map=array('news_article'=>insert_lang_comcode_attachments(2,$news_article,'news',strval($id)));
	$GLOBALS['SITE_DB']->query_update('news',$map,array('id'=>$id),'',1);

	log_it('ADD_NEWS',strval($id),$title);

	if (function_exists('xmlrpc_encode'))
	{
		if (function_exists('set_time_limit')) @set_time_limit(0);
		
		// Send out on RSS cloud
		$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'news_rss_cloud WHERE register_time<'.strval(time()-25*60*60));
		$start=0;
		do
		{
			$listeners=$GLOBALS['SITE_DB']->query_select('news_rss_cloud',array('*'),NULL,'',100,$start);
			foreach ($listeners as $listener)
			{
				$data=$listener['watching_channel'];
				if ($listener['rem_protocol']=='xml-rpc')
				{
					$request=xmlrpc_encode_request($listener['rem_procedure'],$data);
					$length=strlen($request);
					$_length=strval($length);
$packet=<<<END
POST /{$listener['rem_path']} HTTP/1.0
Host: {$listener['rem_ip']}
Content-Type: text/xml
Content-length: {$_length}

{$request}
END;
				}
				$errno=0;
				$errstr='';
				$mysock=@fsockopen($listener['rem_ip'],$listener['rem_port'],$errno,$errstr,6.0);
				if ($mysock!==false)
				{
					@fwrite($mysock,$packet);
					@fclose($mysock);
				}
				$start+=100;
			}
		}
		while (array_key_exists(0,$listeners));
	}

	require_code('seo2');
	seo_meta_set_for_implicit('news',strval($id),array($title,($news=='')?$news_article:$news/*,$news_article*/),($news=='')?$news_article:$news); // News article could be used, but it's probably better to go for the summary only to avoid crap

	if ($validated==1)
	{
		decache('main_news');
		decache('side_news');
		decache('side_news_archive');
		decache('bottom_news');

		dispatch_news_notification($id,$title,$main_news_category_id);
	}

	if (($validated==1) && (get_option('site_closed')=='0') && (ocp_srv('HTTP_HOST')!='127.0.0.1') && (ocp_srv('HTTP_HOST')!='localhost') && (has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news',strval($main_news_category_id))))
	{
		$_ping_url=str_replace('{url}',urlencode(get_base_url()),str_replace('{rss}',urlencode(find_script('backend')),str_replace('{title}',urlencode(get_site_name()),get_option('ping_url'))));
		$ping_urls=explode(chr(10),$_ping_url);
		foreach ($ping_urls as $ping_url)
		{
			$ping_url=trim($ping_url);
			if ($ping_url!='') http_download_file($ping_url,NULL,false);
		}
	}

	return $id;
}

/**
 * Edit a news entry.
 *
 * @param  AUTO_LINK			The ID of the news to edit
 * @param  SHORT_TEXT		The news title
 * @param  LONG_TEXT			The news summary (or if not an article, the full news)
 * @param  ID_TEXT			The news author (possibly, a link to an existing author in the system, but does not need to be)
 * @param  BINARY				Whether the news has been validated
 * @param  BINARY				Whether the news may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the news may have trackbacks
 * @param  LONG_TEXT			Notes for the news
 * @param  LONG_TEXT			The news entry (blank means no entry)
 * @param  AUTO_LINK			The primary news category (NULL: personal)
 * @param  ?array				The IDs of the news categories that this is in (NULL: do not change)
 * @param  SHORT_TEXT		Meta keywords
 * @param  LONG_TEXT			Meta description
 * @param  ?URLPATH			URL to the image for the news entry (blank: use cat image) (NULL: don't delete existing)
 * @param  ?TIME				Recorded add time (NULL: leave alone)
 */
function edit_news($id,$title,$news,$author,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$news_article,$main_news_category,$news_category,$meta_keywords,$meta_description,$image,$time=NULL)
{
	$rows=$GLOBALS['SITE_DB']->query_select('news',array('title','news','news_article','submitter'),array('id'=>$id),'',1);
	$_title=$rows[0]['title'];
	$_news=$rows[0]['news'];
	$_news_article=$rows[0]['news_article'];
	
	require_code('urls2');

	suggest_new_idmoniker_for('news','view',strval($id),$title);

	require_code('attachments2');
	require_code('attachments3');

	if (!addon_installed('unvalidated')) $validated=1;

	require_code('submit');
	$just_validated=(!content_validated('news',strval($id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('news',strval($id));
	}

	$map=array('news_category'=>$main_news_category,'news_article'=>update_lang_comcode_attachments($_news_article,$news_article,'news',strval($id),NULL,false,$rows[0]['submitter']),'edit_date'=>time(),'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'validated'=>$validated,'title'=>lang_remap_comcode($_title,$title),'news'=>lang_remap_comcode($_news,$news),'author'=>$author);

	if (!is_null($time)) $map['date_and_time']=$time;

	if (!is_null($image))
	{
		$map['news_image']=$image;
		require_code('files2');
		delete_upload('uploads/grepimages','news','news_image','id',$id,$image);
	}

	/*$news_categories=$news_category[0];
	foreach ($news_category as $key=>$value)
	{
		if($key>0) $news_categories.=','.$value;
	}*/

	if (!is_null($news_category))
	{
		$GLOBALS['SITE_DB']->query_delete('news_category_entries',array('news_entry'=>$id));

		foreach ($news_category as $value)
		{
			$GLOBALS['SITE_DB']->query_insert('news_category_entries',array('news_entry'=>$id,'news_entry_category'=>$value));
		}
	}

	log_it('EDIT_NEWS',strval($id),$title);

	$GLOBALS['SITE_DB']->query_update('news',$map,array('id'=>$id),'',1);

	$self_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);

	if ($just_validated)
	{
		dispatch_news_notification($id,$title,$main_news_category);
	}

	require_code('seo2');
	seo_meta_set_for_explicit('news',strval($id),$meta_keywords,$meta_description);

	decache('main_news');
	decache('side_news');
	decache('side_news_archive');
	decache('bottom_news');

	if (($validated==1) && (has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news',strval($main_news_category))))
	{
		$_ping_url=str_replace('{url}',urlencode(get_base_url()),str_replace('{rss}',urlencode(find_script('backend')),str_replace('{title}',urlencode(get_site_name()),get_option('ping_url'))));
		$ping_urls=explode(',',$_ping_url);
		foreach ($ping_urls as $ping_url)
		{
			$ping_url=trim($ping_url);
			if ($ping_url!='') http_download_file($ping_url,NULL,false);
		}
	}

	update_spacer_post($allow_comments!=0,'news',strval($id),$self_url,$title,get_value('comment_forum__news'));
}

/**
 * Send out a notification of some new news.
 *
 * @param  AUTO_LINK		The ID of the news
 * @param  SHORT_TEXT	The title
 * @param  AUTO_LINK		The main news category
 */
function dispatch_news_notification($id,$title,$main_news_category)
{
	$self_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);

	$is_blog=!is_null($GLOBALS['SITE_DB']->query_value('news_categories','nc_owner',array('id'=>$main_news_category)));

	require_code('notifications');
	require_lang('news');
	if ($is_blog)
	{
		$subject=do_lang('BLOG_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$title);
		$mail=do_lang('BLOG_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($title),array($self_url->evaluate()));
		dispatch_notification('blog_post',strval($id),$subject,$mail);
	} else
	{
		$subject=do_lang('NEWS_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$title);
		$mail=do_lang('NEWS_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($title),array($self_url->evaluate()));
		dispatch_notification('news_entry',strval($id),$subject,$mail);
	}
}

/**
 * Delete a news entry.
 *
 * @param  AUTO_LINK		The ID of the news to edit
 */
function delete_news($id)
{
	$rows=$GLOBALS['SITE_DB']->query_select('news',array('title','news','news_article'),array('id'=>$id),'',1);
	$title=$rows[0]['title'];
	$news=$rows[0]['news'];
	$news_article=$rows[0]['news_article'];

	$_title=get_translated_text($title);
	log_it('DELETE_NEWS',strval($id),$_title);

	require_code('files2');
	delete_upload('uploads/grepimages','news','news_image','id',$id);

	$GLOBALS['SITE_DB']->query_delete('news',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('news_category_entries',array('news_entry'=>$id));

	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'news','rating_for_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'news','trackback_for_id'=>$id));

	delete_lang($title);
	delete_lang($news);
	require_code('attachments2');
	require_code('attachments3');
	if (!is_null($news_article)) delete_lang_comcode_attachments($news_article,'news',strval($id));

	require_code('seo2');
	seo_meta_erase_storage('news',strval($id));

	decache('main_news');
	decache('side_news');
	decache('side_news_archive');
	decache('bottom_news');
}

/**
 * Get a nice formatted XHTML list of news categories.
 *
 * @param  ?mixed			The selected news category. Array or AUTO_LINK (NULL: personal)
 * @param  boolean		Whether to add all personal categories into the list (for things like the adminzone, where all categories must be shown, regardless of permissions)
 * @param  boolean		Whether to only show for what may be added to by the current member
 * @param  boolean		Whether to limit to only existing cats (otherwise we dynamically add unstarted blogs)
 * @param  ?boolean		Whether to limit to only show blog categories (NULL: don't care, true: blogs only, false: no blogs)
 * @param  boolean		Whether to prefer to choose a non-blog category as the default
 * @return tempcode		The tempcode for the news category select list
 */
function nice_get_news_categories($it=NULL,$show_all_personal_categories=false,$addable_filter=false,$only_existing=false,$only_blogs=NULL,$prefer_not_blog_selected=false)
{
	if (!is_array($it)) $it=array($it);

	if ($only_blogs===true)
	{
		$where='WHERE nc_owner IS NOT NULL';
	}
	elseif ($only_blogs===false)
	{
		$where='WHERE nc_owner IS NULL';
	} else
	{
		$where='WHERE 1=1';
	}
	$count=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'news_categories '.$where.' ORDER BY id');
	if ($count>500) // Uh oh, loads, need to limit things more
	{
		$where.=' AND (nc_owner IS NULL OR nc_owner='.strval(get_member()).')';
	}
	$_cats=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'news_categories '.$where.' ORDER BY id');

	foreach ($_cats as $i=>$cat)
	{
		$_cats[$i]['nice_title']=get_translated_text($cat['nc_title']);
	}
	global $M_SORT_KEY;
	$M_SORT_KEY='nice_title';
	usort($_cats,'multi_sort');

	$categories=new ocp_tempcode();
	$add_cat=true;

	foreach ($_cats as $cat)
	{
		if ($cat['nc_owner']==get_member()) $add_cat=false;

		if (!has_category_access(get_member(),'news',strval($cat['id']))) continue;
		if (($addable_filter) && (!has_submit_permission('high',get_member(),get_ip_address(),'cms_news',array('news',$cat['id'])))) continue;

		if (is_null($cat['nc_owner']))
		{
			$li=form_input_list_entry(strval($cat['id']),($it!=array(NULL)) && in_array($cat['id'],$it),$cat['nice_title'].' (#'.strval($cat['id']).')');
			$categories->attach($li);
		} else
		{
			if ((((!is_null($cat['nc_owner'])) && (has_specific_permission(get_member(),'can_submit_to_others_categories'))) || (($cat['nc_owner']==get_member()) && (!is_guest()))) || ($show_all_personal_categories))
				$categories->attach(form_input_list_entry(strval($cat['id']),(($cat['nc_owner']==get_member()) && ((!$prefer_not_blog_selected) && (in_array(NULL,$it)))) || (in_array($cat['id'],$it)),do_lang('MEMBER_CATEGORY',$GLOBALS['FORUM_DRIVER']->get_username($cat['nc_owner'])).' (#'.strval($cat['id']).')'));
		}
	}

	if ((!$only_existing) && (has_specific_permission(get_member(),'have_personal_category','cms_news')) && ($add_cat) && (!is_guest()))
	{
		$categories->attach(form_input_list_entry('personal',(!$prefer_not_blog_selected) && in_array(NULL,$it),do_lang_tempcode('MEMBER_CATEGORY',do_lang_tempcode('_NEW',escape_html($GLOBALS['FORUM_DRIVER']->get_username(get_member()))))));
	}

	return $categories;
}

/**
 * Get a nice formatted XHTML list of news.
 *
 * @param  ?AUTO_LINK	The selected news entry (NULL: none)
 * @param  ?MEMBER		Limit news to those submitted by this member (NULL: show all)
 * @param  boolean		Whether to only show for what may be edited by the current member
 * @param  boolean		Whether to only show blog posts
 * @return tempcode		The list
 */
function nice_get_news($it,$only_owned=NULL,$editable_filter=false,$only_in_blog=false)
{
	$where=is_null($only_owned)?'1':'submitter='.strval($only_owned);
	if ($only_in_blog)
	{
		$rows=$GLOBALS['SITE_DB']->query('SELECT n.* FROM '.get_table_prefix().'news n JOIN '.get_table_prefix().'news_categories c ON c.id=n.news_category AND '.$where.' AND nc_owner IS NOT NULL ORDER BY date_and_time DESC',300/*reasonable limit*/);
	} else
	{
		$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'news WHERE '.$where.' ORDER BY date_and_time DESC',300/*reasonable limit*/);
	}

	if (count($rows)==300) attach_message(do_lang_tempcode('TOO_MUCH_CHOOSE__RECENT_ONLY',escape_html(number_format(300))),'warn');

	$out=new ocp_tempcode();
	foreach ($rows as $myrow)
	{
		if (!has_category_access(get_member(),'news',strval($myrow['news_category']))) continue;
		if (($editable_filter) && (!has_edit_permission('high',get_member(),$myrow['submitter'],'cms_news',array('news',$myrow['news_category'])))) continue;

		$selected=($myrow['id']==$it);

		$out->attach(form_input_list_entry(strval($myrow['id']),$selected,get_translated_text($myrow['title'])));
	}

	return $out;
}

